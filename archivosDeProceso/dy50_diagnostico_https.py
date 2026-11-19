#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Diagnóstico del sensor DY50 (compatible R30x/R307/ZFM) + envío HTTPS a Laravel.

Pensado para ejecutarse en Thonny (F5) en Raspberry Pi OS.

IMPORTANTE - que se envia a Laravel:
  * Solo fingerprint_id, confidence y el nombre del dispositivo.
  * NUNCA imágenes ni plantillas biométricas.
  * Solo la opción 6 (Buscar una huella) envía un evento real.
  * La opción 7 envía un evento SIMULADO (sin sensor) para probar la red.
"""
import json
import os
import socket
import ssl
import sys
import time
import urllib.error
import urllib.request

try:
    import serial
except ImportError:
    sys.exit("Falta pyserial. En la Raspberry ejecuta:  sudo apt install python3-serial\n"
             "y en Thonny elegí el Python del sistema (Tools > Options > Interpreter).")

# =====================================================================
#  CONFIGURACION  (esto es lo único que tenés que tocar)
# =====================================================================
# --- Sensor ---
PUERTO = "/dev/serial0"
BAUDIOS = 57600                  # el DY50 sale de fábrica a 57600
DIRECCION = 0xFFFFFFFF           # dirección de fábrica
PASSWORD = 0x00000000            # contraseña de fábrica

# --- Laravel (HTTPS) ---
# Usá la IP (o el nombre) de la PC servidor. Tiene que ser EXACTAMENTE
# la que lleva el certificado como SAN.
URL_EVENTOS = "https://192.168.100.150:8443/api/dy50/eventos"
API_TOKEN = "PEGA_AQUI_EL_MISMO_TOKEN_LARGO_DE_LARAVEL"
DISPOSITIVO = "raspberry-1"      # nombre de este lector (para distinguir si hay varios)

# Certificado de tu CA. La ruta se calcula desde donde esta guardado ESTE
# archivo (no depende de la carpeta que use Thonny).
# Si usás un certificado de una CA publica (p. ej. Tailscale) poné RUTA_CA = ""
RUTA_CA = os.path.join(os.path.dirname(os.path.abspath(__file__)), "certs", "ca.crt")

TIMEOUT_HTTP = 10                # segundos
ESPERA_DEDO = 15                 # segundos que espera que apoyes el dedo
# =====================================================================


class ErrorSensor(Exception):
    pass


CODIGOS = {
    0x00: "OK",
    0x01: "Error al recibir el paquete",
    0x02: "No hay dedo en el sensor",
    0x03: "Fallo al capturar la imagen",
    0x06: "Imagen demasiado borrosa/desordenada",
    0x07: "Imagen con muy pocas características (apoyá mejor el dedo)",
    0x08: "Las huellas no coinciden",
    0x09: "No se encontró coincidencia en la biblioteca",
    0x0A: "Fallo al combinar las dos capturas",
    0x0B: "ID fuera del rango de la biblioteca",
    0x0C: "Error al leer la plantilla",
    0x0D: "Error al subir la plantilla",
    0x0E: "El módulo no puede recibir paquetes de datos",
    0x0F: "Error al subir la imagen",
    0x10: "Error al borrar la plantilla",
    0x11: "Error al vaciar la biblioteca",
    0x13: "Contraseña incorrecta",
    0x15: "No hay imagen válida en el buffer",
    0x18: "Error de escritura en la memoria flash",
}


# ---------------------------------------------------------------------
#  Protocolo del sensor
# ---------------------------------------------------------------------
PID_COMANDO = 0x01
PID_ACK = 0x07


def construir_paquete(pid, datos):
    datos = bytes(datos)
    largo = len(datos) + 2                       # datos + checksum
    cuerpo = bytes([pid]) + largo.to_bytes(2, "big") + datos
    suma = sum(cuerpo) & 0xFFFF
    return b"\xEF\x01" + DIRECCION.to_bytes(4, "big") + cuerpo + suma.to_bytes(2, "big")


def leer_exacto(ser, n):
    datos = ser.read(n)
    if len(datos) != n:
        raise ErrorSensor("El sensor no respondió a tiempo (llegaron %d de %d bytes). "
                          "Revisá cableado TX/RX, alimentación y UART." % (len(datos), n))
    return datos


def leer_paquete(ser, espera=2.0):
    """Lee un paquete del sensor; resincroniza buscando la cabecera EF 01."""
    limite = time.time() + espera
    previo = b""
    while True:
        if time.time() > limite:
            raise ErrorSensor("El sensor no respondió (no llegó la cabecera EF01). "
                              "Revisá cableado TX/RX, alimentación, UART y baudios.")
        b = ser.read(1)
        if not b:
            continue
        if previo == b"\xEF" and b == b"\x01":
            break
        previo = b
    resto = leer_exacto(ser, 7)                  # 4 dirección + 1 pid + 2 largo
    pid = resto[4]
    largo = int.from_bytes(resto[5:7], "big")
    if largo < 2 or largo > 300:
        raise ErrorSensor("Paquete con largo inválido (%d)" % largo)
    cola = leer_exacto(ser, largo)
    datos, suma_rx = cola[:-2], int.from_bytes(cola[-2:], "big")
    suma = (pid + resto[5] + resto[6] + sum(datos)) & 0xFFFF
    if suma != suma_rx:
        raise ErrorSensor("Checksum incorrecto en la respuesta del sensor")
    return pid, datos


def comando(ser, codigo, parametros=b""):
    """Envia un comando y devuelve el payload del ACK (el primer byte es el código de confirmacion)."""
    ser.reset_input_buffer()
    ser.write(construir_paquete(PID_COMANDO, bytes([codigo]) + bytes(parametros)))
    ser.flush()
    pid, datos = leer_paquete(ser)
    if pid != PID_ACK or not datos:
        raise ErrorSensor("Respuesta inesperada del sensor (pid=0x%02X)" % pid)
    return datos


def texto_codigo(c):
    return CODIGOS.get(c, "Código desconocido 0x%02X" % c)


def exigir_ok(datos, que):
    if datos[0] != 0x00:
        raise ErrorSensor("%s: %s (0x%02X)" % (que, texto_codigo(datos[0]), datos[0]))


def verificar_password(ser):
    datos = comando(ser, 0x13, PASSWORD.to_bytes(4, "big"))
    exigir_ok(datos, "Verificar contraseña")


def leer_parametros(ser):
    datos = comando(ser, 0x0F)
    exigir_ok(datos, "Leer parámetros")
    p = datos[1:17]
    if len(p) < 16:
        raise ErrorSensor("Respuesta de parámetros incompleta")
    return {
        "capacidad": int.from_bytes(p[4:6], "big"),
        "seguridad": int.from_bytes(p[6:8], "big"),
        "direccion": "0x%08X" % int.from_bytes(p[8:12], "big"),
        "paquete_código": int.from_bytes(p[12:14], "big"),
        "baudios": int.from_bytes(p[14:16], "big") * 9600,
    }


def contar_plantillas(ser):
    datos = comando(ser, 0x1D)
    exigir_ok(datos, "Contar plantillas")
    return int.from_bytes(datos[1:3], "big")


def capturar_imagen(ser):
    """Devuelve el código de confirmacion (0x00 = hay dedo y se capturo)."""
    return comando(ser, 0x01)[0]


def imagen_a_caracteres(ser, buffer):
    exigir_ok(comando(ser, 0x02, bytes([buffer])), "Convertir imagen")


def esperar_dedo(ser, segundos=ESPERA_DEDO):
    """Espera hasta que haya dedo; devuelve True si capturo la imagen."""
    limite = time.time() + segundos
    while time.time() < limite:
        c = capturar_imagen(ser)
        if c == 0x00:
            return True
        if c not in (0x02, 0x03):
            raise ErrorSensor("Captura: %s (0x%02X)" % (texto_codigo(c), c))
        time.sleep(0.15)
    return False


def esperar_sin_dedo(ser, segundos=10):
    limite = time.time() + segundos
    while time.time() < limite:
        if capturar_imagen(ser) == 0x02:
            return True
        time.sleep(0.2)
    return False


def buscar_en_biblioteca(ser, buffer=1):
    """Devuelve (fingerprint_id, puntaje) o None si no hay coincidencia."""
    capacidad = leer_parametros(ser)["capacidad"] or 200
    params = bytes([buffer]) + (0).to_bytes(2, "big") + capacidad.to_bytes(2, "big")
    datos = comando(ser, 0x04, params)
    if datos[0] == 0x09:
        return None
    exigir_ok(datos, "Buscar")
    return int.from_bytes(datos[1:3], "big"), int.from_bytes(datos[3:5], "big")


# ---------------------------------------------------------------------
#  Envío a Laravel por HTTPS
# ---------------------------------------------------------------------
class SinRedireccion(urllib.request.HTTPRedirectHandler):
    """Si Laravel redirige (p. ej. a /login) lo mostramos como error, no lo seguimos."""
    def redirect_request(self, *args, **kwargs):
        return None


def contexto_tls():
    if RUTA_CA:
        if not os.path.isfile(RUTA_CA):
            raise FileNotFoundError("No existe el certificado de la CA: %s" % RUTA_CA)
        return ssl.create_default_context(cafile=RUTA_CA)
    return ssl.create_default_context()


def explicar_error_tls(texto):
    t = texto.lower()
    if "mismatch" in t or "hostname" in t:
        return ("La IP/nombre de URL_EVENTOS no coincide con el SAN del certificado. "
                "Regenerá server.crt con la IP correcta.")
    if "not yet valid" in t or "expired" in t:
        return ("Certificado vencido o «todavía no válido». Revisá la fecha de la Raspberry "
                "con el comando `date` (sin internet puede estar mal) y la vigencia de server.crt.")
    if "unable to get local issuer" in t or "self-signed" in t or "self signed" in t or "unknown ca" in t:
        return ("La Raspberry no confia en el certificado: falta certs/ca.crt, es otro archivo, "
                "o server.crt no fue firmado por esa CA.")
    return "Revisá certificado, CA y la fecha de la Raspberry."


def enviar_evento(fingerprint_id, confidence, dispositivo=DISPOSITIVO):
    """Envia el evento. Devuelve True si Laravel respondió 2xx."""
    if API_TOKEN.startswith("PEGA_AQUI"):
        print("ERROR: falta configurar API_TOKEN al principio del script.")
        return False

    cuerpo = json.dumps({
        "fingerprint_id": int(fingerprint_id),
        "confidence": int(confidence),
        "device": dispositivo,
    }).encode("utf-8")
    pedido = urllib.request.Request(URL_EVENTOS, data=cuerpo, method="POST", headers={
        "Authorization": "Bearer " + API_TOKEN,
        "Content-Type": "application/json",
        "Accept": "application/json",
    })

    print("Enviando a Laravel por HTTPS: %s" % URL_EVENTOS)
    try:
        abridor = urllib.request.build_opener(
            urllib.request.ProxyHandler({}),                    # ignorar proxies del sistema
            urllib.request.HTTPSHandler(context=contexto_tls()),
            SinRedireccion(),
        )
        with abridor.open(pedido, timeout=TIMEOUT_HTTP) as resp:
            estado = resp.status
            texto = resp.read(2000).decode("utf-8", "replace")
    except urllib.error.HTTPError as e:                          # 4xx / 5xx / redirecciones
        estado = e.code
        texto = e.read(2000).decode("utf-8", "replace")
    except urllib.error.URLError as e:
        motivo = e.reason
        if isinstance(motivo, ssl.SSLError):
            print("ERROR TLS: %s" % motivo)
            print("  -> " + explicar_error_tls(str(motivo)))
        elif isinstance(motivo, (socket.timeout, TimeoutError)):
            print("ERROR: tiempo de espera agotado. Firewall, proxy apagado o red con aislamiento de clientes.")
        else:
            print("ERROR de conexión: %s" % motivo)
            print("  -> Proxy apagado, IP/puerto incorrectos, firewall o red que aisla los equipos.")
        return False
    except (socket.timeout, TimeoutError):
        print("ERROR: tiempo de espera agotado.")
        return False
    except ssl.SSLError as e:
        print("ERROR TLS: %s" % e)
        print("  -> " + explicar_error_tls(str(e)))
        return False
    except (OSError, FileNotFoundError) as e:
        print("ERROR: %s" % e)
        return False

    print("HTTP recibido desde Laravel: %s" % estado)
    print("Respuesta Laravel: %s" % texto.strip())
    if 200 <= estado < 300:
        print("OK: Laravel recibió el evento del DY50.")
        return True
    ayuda = {
        401: "Token distinto al de .env (DY50_API_TOKEN) o faltó php artisan config:clear.",
        404: "Ruta no encontrada: revisá routes/api.php y la URL (/api/dy50/eventos).",
        422: "Laravel rechazó los datos enviados.",
        429: "Demasiadas peticiones seguidas; esperá un minuto.",
        502: "El proxy no llega a Laravel: revisá que php artisan serve esté corriendo.",
    }
    if estado in (301, 302, 303, 307, 308):
        print("  -> Laravel redirigió la petición (suele ser porque la ruta no es de la API).")
    elif estado in ayuda:
        print("  -> " + ayuda[estado])
    return False


# ---------------------------------------------------------------------
#  Opciones del menú
# ---------------------------------------------------------------------
def abrir_sensor():
    """Abre el puerto serie. Si falla devuelve None (la opción 7 sigue funcionando)."""
    try:
        return serial.Serial(PUERTO, BAUDIOS, timeout=0.5)
    except (serial.SerialException, OSError) as e:
        print("AVISO: no pude abrir %s: %s" % (PUERTO, e))
        print("Revisá raspi-config (serial habilitado y consola serial desactivada) y los permisos (grupo dialout).")
        print("Mientras tanto solo funciona la opción 7 (evento simulado).")
        return None


def op_verificar(ser):
    print("Comprobando contraseña...")
    verificar_password(ser)
    print("Contraseña OK: el sensor responde.")
    p = leer_parametros(ser)
    print("Capacidad de la biblioteca: %d huellas" % p["capacidad"])
    print("Nivel de seguridad: %d" % p["seguridad"])
    print("Dirección del módulo: %s" % p["direccion"])
    print("Baudios según el sensor: %d" % p["baudios"])
    print("Huellas guardadas ahora: %d" % contar_plantillas(ser))


def op_contar(ser):
    print("Huellas guardadas en el DY50: %d" % contar_plantillas(ser))


def op_capturar(ser):
    print("Apoyá un dedo en el sensor (%d s)..." % ESPERA_DEDO)
    if esperar_dedo(ser):
        print("Dedo detectado y imagen capturada.")
        print("(Esto NO identifica a nadie: para eso usá la opción 6.)")
    else:
        print("No se detectó ningún dedo.")


def op_registrar(ser):
    capacidad = leer_parametros(ser)["capacidad"]
    try:
        n = int(input("ID donde guardar la huella (0 a %d): " % (capacidad - 1)).strip())
    except ValueError:
        print("ID inválido.")
        return
    if not 0 <= n < capacidad:
        print("ID fuera de rango.")
        return
    print("Primera captura: apoyá el dedo...")
    if not esperar_dedo(ser):
        print("No se detectó el dedo.")
        return
    imagen_a_caracteres(ser, 1)
    print("Bien. Levantá el dedo.")
    esperar_sin_dedo(ser)
    print("Segunda captura: apoyá EL MISMO dedo otra vez...")
    if not esperar_dedo(ser):
        print("No se detectó el dedo.")
        return
    imagen_a_caracteres(ser, 2)
    exigir_ok(comando(ser, 0x05), "Combinar capturas (¿mismo dedo?)")
    exigir_ok(comando(ser, 0x06, bytes([1]) + n.to_bytes(2, "big")), "Guardar plantilla")
    print("Huella guardada con ID %d." % n)


def op_borrar(ser):
    try:
        n = int(input("ID de la huella a borrar: ").strip())
    except ValueError:
        print("ID inválido.")
        return
    exigir_ok(comando(ser, 0x0C, n.to_bytes(2, "big") + (1).to_bytes(2, "big")), "Borrar")
    print("Huella %d borrada." % n)


def op_buscar(ser):
    """Opción 6: busca la huella en el DY50 y, si la encuentra, la envia a Laravel."""
    print("Apoyá un dedo REGISTRADO en el sensor (%d s)..." % ESPERA_DEDO)
    if not esperar_dedo(ser):
        print("No se detectó ningún dedo.")
        return
    imagen_a_caracteres(ser, 1)
    r = buscar_en_biblioteca(ser)
    if r is None:
        print("Sin coincidencia: esa huella no está guardada en el DY50 (no se envía nada a Laravel).")
        return
    fid, puntaje = r
    print("Coincidencia encontrada.")
    print("ID de la huella: %d" % fid)
    print("Puntaje de coincidencia: %d" % puntaje)
    enviar_evento(fid, puntaje)


def op_simulado(ser):
    print("Evento SIMULADO (no usa el sensor): sirve para probar red, certificado y token.")
    enviar_evento(0, 0, DISPOSITIVO + "-simulado")


def op_vaciar(ser):
    if input("Esto BORRA todas las huellas del DY50. Escribí SI para confirmar: ").strip() == "SI":
        exigir_ok(comando(ser, 0x0D), "Vaciar biblioteca")
        print("Biblioteca vaciada.")
    else:
        print("Cancelado.")


MENU = [
    ("1", "Verificar el sensor (contraseña y parámetros)", op_verificar),
    ("2", "Contar huellas guardadas", op_contar),
    ("3", "Capturar imagen (solo detecta que hay un dedo)", op_capturar),
    ("4", "Registrar una huella nueva", op_registrar),
    ("5", "Borrar una huella", op_borrar),
    ("6", "Buscar una huella y enviar el ID a Laravel", op_buscar),
    ("7", "Enviar un evento SIMULADO a Laravel (sin sensor)", op_simulado),
    ("8", "Vaciar toda la biblioteca del DY50", op_vaciar),
    ("0", "Salir", None),
]


def main():
    ser = abrir_sensor()
    acciones = {k: f for k, _, f in MENU}
    try:
        while True:
            print("\n===== DY50 + Laravel =====")
            for k, t, _ in MENU:
                print("%s. %s" % (k, t))
            op = input("Opción: ").strip()
            if op == "0":
                break
            if op not in acciones:
                print("Opción no valida.")
                continue
            if ser is None and op != "7":
                print("No hay conexión con el sensor (ver el aviso del inicio).")
                continue
            try:
                acciones[op](ser)
            except ErrorSensor as e:
                print("ERROR del sensor: %s" % e)
            except KeyboardInterrupt:
                print("\nInterrumpido.")
    finally:
        if ser is not None:
            ser.close()
        print("Puerto cerrado.")


if __name__ == "__main__":
    main()
