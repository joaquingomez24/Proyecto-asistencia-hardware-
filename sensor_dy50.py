#!/usr/bin/env python3
"""
Acceso al lector de huellas DY50 (compatible con los módulos R307 / ZFM-60) por puerto serie.

Solo necesita `pyserial` (en Raspberry Pi OS suele venir instalado: python3-serial).

Este archivo es lo ÚNICO que habla con el hardware. El agente (dy50_agente.py) usa solamente
estos tres métodos, así que si preferís seguir con la librería que ya usaban en Thonny
(pyfingerprint, adafruit_fingerprint...), alcanza con crear una clase con los mismos tres métodos:

    leer_huella(timeout, avisar, latido)            -> (posicion, confianza)
    registrar_huella(posicion, timeout, avisar, latido)
    borrar_huella(posicion)

  - avisar(texto): mostrarle un mensaje a la persona ("Apoyá el dedo", "Retirá el dedo"...).
  - latido(): se llama seguido mientras se espera; si devuelve False hay que abandonar (lanzar Cancelado).
  - Excepciones: TiempoAgotado, Cancelado, HuellaNoEncontrada, HuellaDuplicada, ErrorSensor.

Para comprobar la conexión con el módulo (sin Laravel):
    python3 sensor_dy50.py --probar --puerto /dev/serial0
"""
import argparse
import struct
import sys
import time

CABECERA = b"\xEF\x01"
DIRECCION = b"\xFF\xFF\xFF\xFF"

# Tipos de paquete
PID_COMANDO = 0x01
PID_ACK = 0x07

# Códigos de confirmación del módulo
OK = 0x00
ERROR_PAQUETE = 0x01
SIN_DEDO = 0x02
FALLO_IMAGEN = 0x03
IMAGEN_BORROSA = 0x06
POCAS_CARACTERISTICAS = 0x07
NO_ENCONTRADA = 0x09
ENROLAMIENTO_DISTINTO = 0x0A
POSICION_INVALIDA = 0x0B
IMAGEN_INVALIDA = 0x15
ERROR_FLASH = 0x18

# Comandos
CMD_GEN_IMG = 0x01
CMD_IMG2TZ = 0x02
CMD_BUSCAR = 0x04
CMD_CREAR_MODELO = 0x05
CMD_GUARDAR = 0x06
CMD_BORRAR = 0x0C
CMD_VACIAR = 0x0D
CMD_PARAMETROS = 0x0F
CMD_VERIFICAR_PASSWORD = 0x13
CMD_CANTIDAD = 0x1D

IMAGEN_MALA = (IMAGEN_BORROSA, POCAS_CARACTERISTICAS, IMAGEN_INVALIDA)


class ErrorSensor(Exception):
    """El módulo no respondió o respondió con un error."""


class TiempoAgotado(Exception):
    """No se apoyó el dedo antes de vencer el plazo de una captura."""


class DedoNoRetirado(Exception):
    """No se retiró el dedo del sensor antes de la siguiente captura."""


class Cancelado(Exception):
    """La orden fue cancelada o venció en el servidor."""


class HuellaNoEncontrada(Exception):
    """La huella leída no está guardada en el lector."""


class HuellaDuplicada(Exception):
    """Ese dedo ya estaba registrado en otra posición."""

    def __init__(self, posicion):
        super().__init__("huella ya registrada en la posición %s" % posicion)
        self.posicion = posicion


class SensorDY50:
    def __init__(self, conexion, password=0):
        """`conexion`: objeto con read(n)/write(bytes) (un serial.Serial)."""
        self.con = conexion
        self.password = password

    @classmethod
    def abrir(cls, puerto="/dev/serial0", baudios=57600, password=0):
        import serial  # pyserial

        sensor = cls(serial.Serial(puerto, baudios, timeout=1.5), password)
        sensor.verificar_password()
        return sensor

    # ── Protocolo ────────────────────────────────────────────────────────────
    def _leer(self, cantidad):
        datos = self.con.read(cantidad)
        if len(datos) != cantidad:
            raise ErrorSensor("el lector no respondió (revisá cables, puerto y baudios)")
        return datos

    def _enviar(self, comando, datos=b""):
        contenido = bytes([comando]) + datos
        cuerpo = bytes([PID_COMANDO]) + struct.pack(">H", len(contenido) + 2) + contenido
        paquete = CABECERA + DIRECCION + cuerpo + struct.pack(">H", sum(cuerpo) & 0xFFFF)

        if hasattr(self.con, "reset_input_buffer"):
            self.con.reset_input_buffer()
        self.con.write(paquete)
        return self._leer_ack()

    def _leer_ack(self):
        recibido = b""
        for _ in range(64):  # busca la cabecera 0xEF01 entre posibles bytes sueltos
            recibido = (recibido + self._leer(1))[-2:]
            if recibido == CABECERA:
                break
        else:
            raise ErrorSensor("respuesta inválida del lector")

        resto = self._leer(7)                       # dirección(4) + tipo(1) + largo(2)
        tipo = resto[4]
        largo = struct.unpack(">H", resto[5:7])[0]
        cuerpo = self._leer(largo)                  # contenido + suma(2)
        contenido, suma = cuerpo[:-2], struct.unpack(">H", cuerpo[-2:])[0]

        esperada = (tipo + (largo >> 8) + (largo & 0xFF) + sum(contenido)) & 0xFFFF
        if esperada != suma or tipo != PID_ACK or not contenido:
            raise ErrorSensor("respuesta dañada del lector")

        return contenido[0], contenido[1:]

    # ── Comandos básicos ─────────────────────────────────────────────────────
    def verificar_password(self):
        codigo, _ = self._enviar(CMD_VERIFICAR_PASSWORD, struct.pack(">I", self.password))
        if codigo != OK:
            raise ErrorSensor("el lector rechazó la contraseña (código 0x%02X)" % codigo)

    def capturar_imagen(self):
        return self._enviar(CMD_GEN_IMG)[0]

    def convertir(self, buffer):
        return self._enviar(CMD_IMG2TZ, bytes([buffer]))[0]

    def buscar(self, buffer=1):
        """Devuelve (codigo, (posicion, puntaje) | None)."""
        capacidad = self.capacidad()
        codigo, params = self._enviar(CMD_BUSCAR, bytes([buffer]) + struct.pack(">HH", 0, capacidad))
        if codigo == OK and len(params) >= 4:
            return codigo, struct.unpack(">HH", params[:4])
        return codigo, None

    def crear_modelo(self):
        return self._enviar(CMD_CREAR_MODELO)[0]

    def guardar(self, posicion, buffer=1):
        return self._enviar(CMD_GUARDAR, bytes([buffer]) + struct.pack(">H", posicion))[0]

    def capacidad(self):
        codigo, params = self._enviar(CMD_PARAMETROS)
        if codigo != OK or len(params) < 6:
            raise ErrorSensor("no se pudo leer la capacidad del lector")
        return struct.unpack(">H", params[4:6])[0]

    def cantidad_guardadas(self):
        codigo, params = self._enviar(CMD_CANTIDAD)
        if codigo != OK or len(params) < 2:
            raise ErrorSensor("no se pudo leer la cantidad de huellas")
        return struct.unpack(">H", params[:2])[0]

    # ── Operaciones de alto nivel (las que usa el agente) ────────────────────
    def _esperar_dedo(self, fin, latido, mensaje_timeout="No se detectó el dedo dentro del tiempo permitido."):
        while True:
            if latido is not None and latido() is False:
                raise Cancelado()
            if time.monotonic() >= fin:
                raise TiempoAgotado(mensaje_timeout)
            codigo = self.capturar_imagen()
            if codigo == OK:
                return
            if codigo not in (SIN_DEDO, FALLO_IMAGEN, ERROR_PAQUETE):
                raise ErrorSensor("error al capturar la imagen (código 0x%02X)" % codigo)
            time.sleep(0.1)

    def _esperar_sin_dedo(self, latido, limite=15):
        """Espera hasta que la persona retire completamente el dedo."""
        fin = time.monotonic() + limite

        while time.monotonic() < fin:
            if latido is not None and latido() is False:
                raise Cancelado()

            codigo = self.capturar_imagen()
            if codigo == SIN_DEDO:
                return True
            if codigo not in (OK, FALLO_IMAGEN, ERROR_PAQUETE):
                raise ErrorSensor(
                    "Error al comprobar si retiraste el dedo (código 0x%02X)." % codigo
                )

            time.sleep(0.1)

        return False

    def _captura_valida(self, buffer, timeout, avisar, latido, mensaje_timeout):
        """Espera un dedo y deja su imagen convertida en el buffer indicado (reintenta si sale borrosa)."""
        while True:
            fin = time.monotonic() + timeout
            self._esperar_dedo(fin, latido, mensaje_timeout)
            codigo = self.convertir(buffer)
            if codigo == OK:
                return
            if codigo not in IMAGEN_MALA:
                raise ErrorSensor("no se pudo procesar la imagen (código 0x%02X)" % codigo)
            avisar("La imagen no es clara. Retirá el dedo y volvé a apoyarlo.")
            if not self._esperar_sin_dedo(latido):
                raise DedoNoRetirado("No se detectó que retiraras el dedo después de una imagen poco clara.")

    def leer_huella(self, timeout, avisar, latido=None):
        avisar("Apoyá el dedo en el lector")
        self._captura_valida(
            1,
            timeout,
            avisar,
            latido,
            "No se detectó el dedo dentro de %s segundos." % timeout,
        )

        codigo, resultado = self.buscar(1)
        if codigo == OK and resultado is not None:
            return resultado[0], resultado[1]
        if codigo == NO_ENCONTRADA:
            raise HuellaNoEncontrada()
        raise ErrorSensor("falló la búsqueda (código 0x%02X)" % codigo)

    def registrar_huella(self, posicion, timeout, avisar, latido=None):
        avisar("Apoyá el dedo en el lector (tenés %s segundos)" % timeout)
        self._captura_valida(
            1,
            timeout,
            avisar,
            latido,
            "No se detectó el primer apoyo dentro de %s segundos." % timeout,
        )

        codigo, existente = self.buscar(1)
        if codigo == OK and existente is not None:
            raise HuellaDuplicada(existente[0])
        if codigo not in (OK, NO_ENCONTRADA):
            raise ErrorSensor("falló la búsqueda de duplicados (código 0x%02X)" % codigo)

        avisar("Retirá el dedo completamente")
        if not self._esperar_sin_dedo(latido, limite=15):
            raise DedoNoRetirado(
                "No se detectó que retiraras el dedo dentro de 15 segundos. Volvé a intentarlo."
            )

        avisar("Apoyá el mismo dedo otra vez (tenés %s segundos)" % timeout)
        self._captura_valida(
            2,
            timeout,
            avisar,
            latido,
            "No se detectó el segundo apoyo dentro de %s segundos." % timeout,
        )

        codigo = self.crear_modelo()
        if codigo == ENROLAMIENTO_DISTINTO:
            raise ErrorSensor(
                "Las dos lecturas no coinciden. Usá el mismo dedo y apoyalo de manera similar."
            )
        if codigo != OK:
            raise ErrorSensor("No se pudo crear el modelo de huella (código 0x%02X)" % codigo)

        codigo = self.guardar(posicion)
        if codigo == POSICION_INVALIDA:
            raise ErrorSensor("La posición %s no existe en este lector." % posicion)
        if codigo != OK:
            raise ErrorSensor("No se pudo guardar la huella en el lector (código 0x%02X)" % codigo)

        avisar("Huella guardada")

    def borrar_huella(self, posicion):
        codigo, _ = self._enviar(CMD_BORRAR, struct.pack(">HH", posicion, 1))
        if codigo != OK:
            raise ErrorSensor("no se pudo borrar la posición %s (código 0x%02X)" % (posicion, codigo))

    def vaciar_memoria(self):
        codigo, _ = self._enviar(CMD_VACIAR)
        if codigo != OK:
            raise ErrorSensor("no se pudo vaciar la memoria del lector (código 0x%02X)" % codigo)


class SensorSimulado:
    """Lector falso para probar todo el circuito sin hardware (python3 dy50_agente.py --simulado)."""

    def __init__(self, posicion_que_lee=1, demora=2.0):
        self.posicion = posicion_que_lee
        self.demora = demora
        self.guardadas = {posicion_que_lee}

    def _esperar(self, segundos, latido):
        fin = time.monotonic() + segundos
        while time.monotonic() < fin:
            if latido is not None and latido() is False:
                raise Cancelado()
            time.sleep(0.1)

    def leer_huella(self, timeout, avisar, latido=None):
        avisar("Apoyá el dedo en el lector (simulado)")
        self._esperar(self.demora, latido)
        return self.posicion, 200

    def registrar_huella(self, posicion, timeout, avisar, latido=None):
        avisar("Apoyá el dedo en el lector (simulado)")
        self._esperar(self.demora, latido)
        avisar("Retirá el dedo")
        self._esperar(1, latido)
        avisar("Apoyá el mismo dedo otra vez")
        self._esperar(self.demora, latido)
        self.guardadas.add(posicion)
        self.posicion = posicion

    def borrar_huella(self, posicion):
        self.guardadas.discard(posicion)


def _probar(puerto, baudios, password):
    print("Abriendo %s a %s baudios…" % (puerto, baudios))
    sensor = SensorDY50.abrir(puerto, baudios, password)
    print("Contraseña verificada. Capacidad: %s huellas. Guardadas: %s." % (sensor.capacidad(), sensor.cantidad_guardadas()))
    print("Apoyá un dedo para buscarlo (10 s)…")
    try:
        posicion, puntaje = sensor.leer_huella(10, print)
        print("Encontrada: posición %s, puntaje %s." % (posicion, puntaje))
    except HuellaNoEncontrada:
        print("El dedo se leyó bien pero no está registrado.")
    except TiempoAgotado:
        print("No se apoyó ningún dedo.")


if __name__ == "__main__":
    ap = argparse.ArgumentParser(description="Prueba de conexión con el DY50")
    ap.add_argument("--probar", action="store_true", required=True)
    ap.add_argument("--puerto", default="/dev/serial0")
    ap.add_argument("--baudios", type=int, default=57600)
    ap.add_argument("--password", type=int, default=0)
    a = ap.parse_args()
    try:
        _probar(a.puerto, a.baudios, a.password)
    except ErrorSensor as e:
        sys.exit("Error: %s" % e)
