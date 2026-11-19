"""Prueba la capa del DY50 contra un módulo falso que habla el protocolo serie (sin hardware)."""
import os
import struct
import sys
import unittest

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

import sensor_dy50 as s  # noqa: E402


class ModuloFalso:
    """Imita al módulo: recibe paquetes de comando y deja listas las respuestas."""

    def __init__(self, capacidad=100, biblioteca=None, imagenes=None):
        self.capacidad = capacidad
        self.biblioteca = dict(biblioteca or {})     # posicion -> id de dedo
        self.imagenes = list(imagenes or [])         # lo que "ve" el sensor en cada captura (None = sin dedo)
        self.buffers = {1: None, 2: None}
        self.imagen = None
        self.salida = bytearray()
        self.corromper = False

    # API tipo serial.Serial
    def reset_input_buffer(self):
        self.salida.clear()

    def read(self, n):
        datos = bytes(self.salida[:n])
        del self.salida[:n]
        return datos

    def write(self, paquete):
        assert paquete[:2] == s.CABECERA and paquete[2:6] == s.DIRECCION
        assert paquete[6] == s.PID_COMANDO
        largo = struct.unpack(">H", paquete[7:9])[0]
        contenido = paquete[9:9 + largo - 2]
        suma = struct.unpack(">H", paquete[9 + largo - 2:9 + largo])[0]
        assert suma == (sum(paquete[6:9]) + sum(contenido)) & 0xFFFF, "suma de verificación incorrecta"
        self._responder(contenido[0], contenido[1:])

    def _ack(self, codigo, params=b""):
        contenido = bytes([codigo]) + params
        cuerpo = bytes([s.PID_ACK]) + struct.pack(">H", len(contenido) + 2) + contenido
        suma = sum(cuerpo) & 0xFFFF
        if self.corromper:
            suma ^= 0x1
        self.salida += s.CABECERA + s.DIRECCION + cuerpo + struct.pack(">H", suma)

    def _responder(self, comando, datos):
        if comando == s.CMD_VERIFICAR_PASSWORD:
            self._ack(s.OK if datos == b"\x00\x00\x00\x00" else 0x13)
        elif comando == s.CMD_GEN_IMG:
            self.imagen = self.imagenes.pop(0) if self.imagenes else None
            self._ack(s.SIN_DEDO if self.imagen is None else s.OK)
        elif comando == s.CMD_IMG2TZ:
            if self.imagen == "borrosa":
                self._ack(s.IMAGEN_BORROSA)
            else:
                self.buffers[datos[0]] = self.imagen
                self._ack(s.OK)
        elif comando == s.CMD_BUSCAR:
            _, inicio, cantidad = struct.unpack(">BHH", datos)
            assert cantidad == self.capacidad
            for pos, dedo in self.biblioteca.items():
                if dedo == self.buffers[datos[0]]:
                    return self._ack(s.OK, struct.pack(">HH", pos, 100))
            self._ack(s.NO_ENCONTRADA, b"\x00\x00\x00\x00")
        elif comando == s.CMD_CREAR_MODELO:
            self._ack(s.OK if self.buffers[1] == self.buffers[2] else s.ENROLAMIENTO_DISTINTO)
        elif comando == s.CMD_GUARDAR:
            _, pos = struct.unpack(">BH", datos)
            if pos > self.capacidad:
                return self._ack(s.POSICION_INVALIDA)
            self.biblioteca[pos] = self.buffers[1]
            self._ack(s.OK)
        elif comando == s.CMD_BORRAR:
            pos, cantidad = struct.unpack(">HH", datos)
            self.biblioteca.pop(pos, None)
            self._ack(s.OK)
        elif comando == s.CMD_VACIAR:
            self.biblioteca.clear()
            self._ack(s.OK)
        elif comando == s.CMD_PARAMETROS:
            self._ack(s.OK, struct.pack(">HHHHIHH", 0, 0, self.capacidad, 3, 0xFFFFFFFF, 2, 6))
        elif comando == s.CMD_CANTIDAD:
            self._ack(s.OK, struct.pack(">H", len(self.biblioteca)))
        else:
            self._ack(0x01)


def sensor(**kw):
    modulo = ModuloFalso(**kw)
    return s.SensorDY50(modulo), modulo


class PruebasSensor(unittest.TestCase):
    def test_password_y_capacidad(self):
        sen, _ = sensor(capacidad=162)
        sen.verificar_password()
        self.assertEqual(sen.capacidad(), 162)

    def test_lee_huella_registrada(self):
        sen, _ = sensor(biblioteca={3: "A"}, imagenes=[None, None, "A"])
        mensajes = []
        self.assertEqual(sen.leer_huella(5, mensajes.append), (3, 100))
        self.assertIn("Apoyá el dedo en el lector", mensajes)

    def test_huella_no_registrada(self):
        sen, _ = sensor(biblioteca={3: "A"}, imagenes=["B"])
        with self.assertRaises(s.HuellaNoEncontrada):
            sen.leer_huella(5, lambda m: None)

    def test_tiempo_agotado(self):
        sen, _ = sensor(imagenes=[])
        with self.assertRaises(s.TiempoAgotado):
            sen.leer_huella(0.3, lambda m: None)

    def test_enrolar_agota_primera_captura_con_mensaje_especifico(self):
        sen, _ = sensor(imagenes=[])
        with self.assertRaises(s.TiempoAgotado) as cm:
            sen.registrar_huella(5, 0.2, lambda m: None)
        self.assertIn("primer apoyo", str(cm.exception))
        self.assertIn("segundos", str(cm.exception))

    def test_enrolar_agota_segunda_captura_con_mensaje_especifico(self):
        sen, _ = sensor(imagenes=["A", None])
        with self.assertRaises(s.TiempoAgotado) as cm:
            sen.registrar_huella(5, 0.2, lambda m: None)
        self.assertIn("segundo apoyo", str(cm.exception))

    def test_enrolar_informa_si_no_se_retira_el_dedo(self):
        sen, _ = sensor(imagenes=["A"])
        sen._esperar_sin_dedo = lambda latido, limite=15: False
        with self.assertRaises(s.DedoNoRetirado):
            sen.registrar_huella(5, 1, lambda m: None)

    def test_cancelado_por_latido(self):
        sen, _ = sensor(imagenes=[])
        with self.assertRaises(s.Cancelado):
            sen.leer_huella(5, lambda m: None, latido=lambda: False)

    def test_imagen_borrosa_se_reintenta(self):
        # 1) borrosa, se retira (sin dedo), 2) buena
        sen, _ = sensor(biblioteca={7: "A"}, imagenes=["borrosa", None, "A"])
        mensajes = []
        self.assertEqual(sen.leer_huella(5, mensajes.append)[0], 7)
        self.assertTrue(any("no es clara" in m for m in mensajes))

    def test_registrar_huella_nueva(self):
        sen, modulo = sensor(imagenes=["A", None, "A"])
        mensajes = []
        sen.registrar_huella(5, 10, mensajes.append)
        self.assertEqual(modulo.biblioteca, {5: "A"})
        self.assertEqual(mensajes[:3], [
            "Apoyá el dedo en el lector (tenés 10 segundos)",
            "Retirá el dedo completamente",
            "Apoyá el mismo dedo otra vez (tenés 10 segundos)",
        ])

    def test_registrar_detecta_duplicado(self):
        sen, modulo = sensor(biblioteca={2: "A"}, imagenes=["A"])
        with self.assertRaises(s.HuellaDuplicada) as cm:
            sen.registrar_huella(5, 10, lambda m: None)
        self.assertEqual(cm.exception.posicion, 2)
        self.assertNotIn(5, modulo.biblioteca)

    def test_registrar_dedos_distintos_falla(self):
        sen, modulo = sensor(imagenes=["A", None, "B"])
        with self.assertRaises(s.ErrorSensor):
            sen.registrar_huella(5, 10, lambda m: None)
        self.assertEqual(modulo.biblioteca, {})

    def test_posicion_invalida(self):
        sen, _ = sensor(capacidad=10, imagenes=["A", None, "A"])
        with self.assertRaises(s.ErrorSensor):
            sen.registrar_huella(50, 10, lambda m: None)

    def test_borrar(self):
        sen, modulo = sensor(biblioteca={4: "A"})
        sen.borrar_huella(4)
        self.assertEqual(modulo.biblioteca, {})

    def test_vaciar_memoria(self):
        sen, modulo = sensor(biblioteca={4: "A", 9: "B"})
        sen.vaciar_memoria()
        self.assertEqual(modulo.biblioteca, {})

    def test_vaciar_memoria_informa_error_del_modulo(self):
        class ModuloQueFalla(ModuloFalso):
            def _responder(self, comando, datos):
                if comando == s.CMD_VACIAR:
                    self._ack(s.ERROR_FLASH)
                else:
                    super()._responder(comando, datos)

        sen = s.SensorDY50(ModuloQueFalla(biblioteca={4: "A"}))
        with self.assertRaisesRegex(s.ErrorSensor, "no se pudo vaciar"):
            sen.vaciar_memoria()

    def test_respuesta_corrupta(self):
        sen, modulo = sensor()
        modulo.corromper = True
        with self.assertRaises(s.ErrorSensor):
            sen.capacidad()

    def test_sin_respuesta(self):
        class Mudo:
            def write(self, d): pass
            def read(self, n): return b""
        with self.assertRaises(s.ErrorSensor):
            s.SensorDY50(Mudo()).capacidad()


if __name__ == "__main__":
    unittest.main()
