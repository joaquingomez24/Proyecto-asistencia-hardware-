#!/usr/bin/env python3
"""
Agente del lector DY50 (corre en la Raspberry Pi).

Reemplaza al menú de la terminal: queda consultando a Laravel cada segundo, y cuando alguien toca
un botón en la página ("Registrar asistencia", "Registrar huella en el lector") Laravel crea una
orden; este programa la toma, opera el DY50 y devuelve el resultado.

    página (navegador)  ->  Laravel (crea la orden)  <-  este agente (consulta, opera el DY50, responde)

Uso típico (desde Thonny o una terminal):
    python3 dy50_agente.py --url https://192.168.100.150:8443 --token TU_TOKEN --cafile /home/alumnos/certs/ca.crt

Para probar sin el lector conectado:
    python3 dy50_agente.py --url http://192.168.100.150:8000 --token TU_TOKEN --simulado

Los valores también pueden venir de variables de entorno: DY50_URL, DY50_TOKEN, DY50_DISPOSITIVO.
Solo usa la librería estándar de Python (más pyserial para hablar con el módulo).
"""
import argparse
import json
import os
import ssl
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

from sensor_dy50 import (Cancelado, DedoNoRetirado, ErrorSensor, HuellaDuplicada,
                         HuellaNoEncontrada, SensorDY50, SensorSimulado, TiempoAgotado)

TIEMPO_ESPERA_DEDO_ENROLAR = 15


def log(mensaje):
    print("[%s] %s" % (time.strftime("%H:%M:%S"), mensaje), flush=True)


class ErrorRed(Exception):
    """No se pudo hablar con Laravel (PC apagada, red caída, proxy cerrado...)."""


class ErrorFatal(Exception):
    """Error que no se arregla reintentando (por ejemplo, token incorrecto)."""


class Api:
    def __init__(self, url, token, dispositivo, contexto_ssl=None, timeout=10):
        self.url = url.rstrip("/")
        self.token = token
        self.dispositivo = dispositivo
        self.contexto = contexto_ssl
        self.timeout = timeout

    def _pedir(self, metodo, ruta, cuerpo=None):
        datos = json.dumps(cuerpo).encode("utf-8") if cuerpo is not None else None
        peticion = urllib.request.Request(self.url + ruta, data=datos, method=metodo)
        peticion.add_header("Authorization", "Bearer " + self.token)
        peticion.add_header("Accept", "application/json")
        if datos is not None:
            peticion.add_header("Content-Type", "application/json")

        try:
            with urllib.request.urlopen(peticion, timeout=self.timeout, context=self.contexto) as r:
                estado, bruto = r.status, r.read()
        except urllib.error.HTTPError as e:
            estado, bruto = e.code, e.read()
        except (urllib.error.URLError, OSError) as e:
            raise ErrorRed(str(getattr(e, "reason", e)))

        try:
            respuesta = json.loads(bruto) if bruto else None
        except ValueError:
            respuesta = None
        return estado, respuesta

    def siguiente(self):
        estado, datos = self._pedir(
            "GET", "/api/dy50/comandos/siguiente?dispositivo=" + urllib.parse.quote(self.dispositivo))
        if estado == 204:
            return None
        if estado == 200 and datos:
            return datos
        if estado == 401:
            raise ErrorFatal("Laravel rechazó el token. Revisá que DY50_TOKEN coincida con el del .env.")
        raise ErrorRed("respuesta inesperada de Laravel: HTTP %s" % estado)

    def progreso(self, id_comando, mensaje):
        """Informa el avance. Devuelve el estado actual de la orden en el servidor."""
        estado, datos = self._pedir("POST", "/api/dy50/comandos/%s/progreso" % id_comando, {"mensaje": mensaje})
        return datos.get("estado") if datos else None

    def resultado(self, id_comando, exito, **extra):
        cuerpo = {"exito": bool(exito)}
        cuerpo.update({k: v for k, v in extra.items() if v is not None})
        estado, datos = self._pedir("POST", "/api/dy50/comandos/%s/resultado" % id_comando, cuerpo)
        return estado, datos


def enviar_resultado(api, id_comando, exito, **extra):
    """El resultado es lo más importante: se reintenta unas veces si la red falla."""
    for intento in range(1, 4):
        try:
            estado, datos = api.resultado(id_comando, exito, **extra)
            if estado == 409:
                log("La orden %s ya estaba cerrada en el servidor (%s)." % (id_comando, (datos or {}).get("mensaje", "")))
            elif estado >= 400:
                log("Laravel respondió HTTP %s al enviar el resultado: %s" % (estado, datos))
            return
        except ErrorRed as e:
            log("No se pudo enviar el resultado (intento %s/3): %s" % (intento, e))
            time.sleep(1)


def ejecutar(api, lector, orden):
    id_orden = orden["id"]
    tipo = orden["tipo"]
    timeout = int(orden.get("timeout_segundos") or 20)
    log("Orden %s: %s" % (id_orden, tipo))

    estado = {"texto": "", "ultimo_envio": 0.0, "cancelada": False}

    def enviar_progreso():
        estado["ultimo_envio"] = time.monotonic()
        try:
            actual = api.progreso(id_orden, estado["texto"])
        except ErrorRed:
            return
        if actual is not None and actual != "en_curso":
            estado["cancelada"] = True

    def avisar(mensaje):
        log("   " + mensaje)
        estado["texto"] = mensaje
        enviar_progreso()

    def latido():
        # El sensor lo llama seguido mientras espera. Cada 2 s se avisa al servidor y se
        # comprueba que la orden siga vigente (si la cancelaron o venció, se abandona).
        if time.monotonic() - estado["ultimo_envio"] >= 2:
            enviar_progreso()
        return not estado["cancelada"]

    try:
        if tipo == "verificar":
            posicion, confianza = lector.leer_huella(timeout, avisar, latido)
            log("   Huella en posición %s (puntaje %s)" % (posicion, confianza))
            enviar_resultado(api, id_orden, True, sensor_id=posicion, confianza=confianza)

        elif tipo == "enrolar":
            posicion = int(orden["sensor_id"])
            lector.registrar_huella(
                posicion,
                min(timeout, TIEMPO_ESPERA_DEDO_ENROLAR),
                avisar,
                latido,
            )
            enviar_resultado(api, id_orden, True, sensor_id=posicion)

        elif tipo == "borrar":
            lector.borrar_huella(int(orden["sensor_id"]))
            enviar_resultado(api, id_orden, True)

        else:
            enviar_resultado(api, id_orden, False, mensaje="Acción desconocida para este agente: %s" % tipo)

    except Cancelado:
        log("   Orden cancelada o vencida en el servidor; se abandona.")
    except TiempoAgotado as e:
        mensaje = str(e) or "No se detectó el dedo dentro del tiempo permitido. Volvé a intentarlo."
        enviar_resultado(api, id_orden, False, mensaje=mensaje)
    except DedoNoRetirado as e:
        enviar_resultado(api, id_orden, False, mensaje=str(e))
    except HuellaNoEncontrada:
        enviar_resultado(api, id_orden, False, mensaje="La huella no está registrada en el lector.")
    except HuellaDuplicada as e:
        enviar_resultado(api, id_orden, False,
                         mensaje="Ese dedo ya está registrado en el lector (posición %s)." % e.posicion)
    except ErrorSensor as e:
        log("   Error del lector: %s" % e)
        enviar_resultado(api, id_orden, False, mensaje="Error del lector: %s" % e)


def crear_contexto_ssl(url, cafile, sin_verificar):
    if not url.lower().startswith("https"):
        return None
    if sin_verificar:
        contexto = ssl.create_default_context()
        contexto.check_hostname = False
        contexto.verify_mode = ssl.CERT_NONE
        return contexto
    return ssl.create_default_context(cafile=cafile) if cafile else ssl.create_default_context()


def main():
    p = argparse.ArgumentParser(description="Agente del lector DY50 para Laravel")
    p.add_argument("--url", default=os.environ.get("DY50_URL"), help="dirección de Laravel o del proxy HTTPS")
    p.add_argument("--token", default=os.environ.get("DY50_TOKEN"), help="DY50_TOKEN del .env de Laravel")
    p.add_argument("--dispositivo", default=os.environ.get("DY50_DISPOSITIVO", "raspberry-1"))
    p.add_argument("--puerto", default="/dev/serial0", help="puerto serie del DY50 (o /dev/ttyUSB0)")
    p.add_argument("--baudios", type=int, default=57600)
    p.add_argument("--cafile", help="certificado de la CA (ca.crt) para validar el HTTPS")
    p.add_argument("--no-verificar-tls", action="store_true", help="no validar el certificado (solo pruebas)")
    p.add_argument("--intervalo", type=float, default=1.0, help="segundos entre consultas a Laravel")
    p.add_argument("--simulado", action="store_true", help="no usar el módulo real: simula las lecturas")
    p.add_argument("--simulado-posicion", type=int, default=1, help="posición que 'lee' el modo simulado")
    a = p.parse_args()

    if not a.url or not a.token:
        p.error("Faltan --url y --token (o las variables DY50_URL y DY50_TOKEN).")

    try:
        lector = (SensorSimulado(a.simulado_posicion) if a.simulado
                  else SensorDY50.abrir(a.puerto, a.baudios))
    except (ErrorSensor, OSError) as e:
        sys.exit("No pude abrir el lector en %s: %s" % (a.puerto, e))

    api = Api(a.url, a.token, a.dispositivo, crear_contexto_ssl(a.url, a.cafile, a.no_verificar_tls))

    log("Agente '%s' iniciado -> %s%s" % (a.dispositivo, a.url, "  (MODO SIMULADO)" if a.simulado else ""))

    fallos_seguidos = 0
    try:
        while True:
            try:
                orden = api.siguiente()
                if fallos_seguidos:
                    log("Conexión con Laravel recuperada.")
                fallos_seguidos = 0
            except ErrorFatal as e:
                sys.exit(str(e))
            except ErrorRed as e:
                if fallos_seguidos % 30 == 0:
                    log("Sin conexión con Laravel (%s). Sigo intentando…" % e)
                fallos_seguidos += 1
                time.sleep(min(5.0, a.intervalo * 3))
                continue

            if orden is None:
                time.sleep(a.intervalo)
            else:
                ejecutar(api, lector, orden)
    except KeyboardInterrupt:
        log("Agente detenido.")


if __name__ == "__main__":
    main()
