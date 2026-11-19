"""Prueba el agente contra un 'Laravel' falso que implementa el contrato de /api/dy50/comandos/*."""
import json
import os
import sys
import threading
import unittest
from http.server import BaseHTTPRequestHandler, HTTPServer

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

import dy50_agente as ag  # noqa: E402
import sensor_dy50 as s  # noqa: E402

TOKEN = "token-de-prueba"


class LaravelFalso(BaseHTTPRequestHandler):
    pendientes = []        # órdenes que 'siguiente' va entregando
    resultados = {}        # id -> cuerpo recibido
    progresos = {}         # id -> [mensajes]
    estado_servidor = "en_curso"

    def log_message(self, *a):
        pass

    def _json(self, codigo, datos=None):
        cuerpo = json.dumps(datos).encode() if datos is not None else b""
        self.send_response(codigo)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(cuerpo)))
        self.end_headers()
        self.wfile.write(cuerpo)

    def _autorizado(self):
        if self.headers.get("Authorization") != "Bearer " + TOKEN:
            self._json(401, {"ok": False, "mensaje": "No autorizado"})
            return False
        return True

    def do_GET(self):
        if not self._autorizado():
            return
        assert self.path.startswith("/api/dy50/comandos/siguiente?dispositivo=")
        if self.pendientes:
            return self._json(200, self.pendientes.pop(0))
        self._json(204)

    def do_POST(self):
        if not self._autorizado():
            return
        cuerpo = json.loads(self.rfile.read(int(self.headers["Content-Length"])))
        partes = self.path.strip("/").split("/")          # api dy50 comandos {id} {accion}
        id_orden, accion = int(partes[3]), partes[4]
        if accion == "progreso":
            self.progresos.setdefault(id_orden, []).append(cuerpo.get("mensaje"))
            return self._json(200, {"ok": True, "estado": self.estado_servidor})
        self.resultados[id_orden] = cuerpo
        self._json(200, {"ok": True, "estado": "completado"})


class PruebasAgente(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.servidor = HTTPServer(("127.0.0.1", 0), LaravelFalso)
        cls.puerto = cls.servidor.server_address[1]
        threading.Thread(target=cls.servidor.serve_forever, daemon=True).start()

    @classmethod
    def tearDownClass(cls):
        cls.servidor.shutdown()

    def setUp(self):
        LaravelFalso.pendientes = []
        LaravelFalso.resultados = {}
        LaravelFalso.progresos = {}
        LaravelFalso.estado_servidor = "en_curso"
        self.api = ag.Api("http://127.0.0.1:%d" % self.puerto, TOKEN, "raspberry-1", timeout=3)

    def test_siguiente_sin_ordenes(self):
        self.assertIsNone(self.api.siguiente())

    def test_siguiente_entrega_orden(self):
        LaravelFalso.pendientes.append({"id": 9, "tipo": "verificar", "timeout_segundos": 5})
        self.assertEqual(self.api.siguiente()["id"], 9)

    def test_token_incorrecto_es_fatal(self):
        api = ag.Api("http://127.0.0.1:%d" % self.puerto, "otro", "raspberry-1")
        with self.assertRaises(ag.ErrorFatal):
            api.siguiente()

    def test_sin_red(self):
        api = ag.Api("http://127.0.0.1:1", TOKEN, "raspberry-1", timeout=1)
        with self.assertRaises(ag.ErrorRed):
            api.siguiente()

    def test_verificar_ok(self):
        orden = {"id": 1, "tipo": "verificar", "timeout_segundos": 5}
        ag.ejecutar(self.api, s.SensorSimulado(3, demora=0.2), orden)
        self.assertEqual(LaravelFalso.resultados[1], {"exito": True, "sensor_id": 3, "confianza": 200})
        self.assertTrue(LaravelFalso.progresos[1])

    def test_verificar_huella_desconocida(self):
        class NoRegistrada:
            def leer_huella(self, t, avisar, latido): raise s.HuellaNoEncontrada()
        ag.ejecutar(self.api, NoRegistrada(), {"id": 2, "tipo": "verificar", "timeout_segundos": 5})
        r = LaravelFalso.resultados[2]
        self.assertFalse(r["exito"])
        self.assertIn("no está registrada", r["mensaje"])

    def test_verificar_tiempo_agotado(self):
        class Lento:
            def leer_huella(self, t, avisar, latido): raise s.TiempoAgotado()
        ag.ejecutar(self.api, Lento(), {"id": 3, "tipo": "verificar", "timeout_segundos": 5})
        self.assertIn("tiempo permitido", LaravelFalso.resultados[3]["mensaje"])

    def test_orden_cancelada_no_envia_resultado(self):
        LaravelFalso.estado_servidor = "cancelado"
        ag.ejecutar(self.api, s.SensorSimulado(1, demora=5), {"id": 4, "tipo": "verificar", "timeout_segundos": 10})
        self.assertNotIn(4, LaravelFalso.resultados)

    def test_enrolar(self):
        ag.ejecutar(self.api, s.SensorSimulado(1, demora=0.1),
                    {"id": 5, "tipo": "enrolar", "sensor_id": 12, "timeout_segundos": 30})
        self.assertEqual(LaravelFalso.resultados[5], {"exito": True, "sensor_id": 12})
        self.assertIn("Retirá el dedo", LaravelFalso.progresos[5])

    def test_enrolar_duplicado(self):
        class Duplicado:
            def registrar_huella(self, p, t, avisar, latido): raise s.HuellaDuplicada(8)
        ag.ejecutar(self.api, Duplicado(), {"id": 6, "tipo": "enrolar", "sensor_id": 12, "timeout_segundos": 30})
        self.assertIn("posición 8", LaravelFalso.resultados[6]["mensaje"])

    def test_enrolar_limita_cada_apoyo_a_15_segundos(self):
        class SinDedo:
            def registrar_huella(self, posicion, timeout, avisar, latido):
                self.timeout = timeout
                raise s.TiempoAgotado("No se detectó el primer apoyo dentro de 15 segundos.")

        lector = SinDedo()
        ag.ejecutar(self.api, lector, {"id": 11, "tipo": "enrolar", "sensor_id": 12, "timeout_segundos": 90})
        self.assertEqual(lector.timeout, 15)
        self.assertEqual(
            LaravelFalso.resultados[11]["mensaje"],
            "No se detectó el primer apoyo dentro de 15 segundos.",
        )

    def test_enrolar_informa_retiro_del_dedo(self):
        class NoRetirado:
            def registrar_huella(self, posicion, timeout, avisar, latido):
                raise s.DedoNoRetirado("No se detectó que retiraras el dedo.")

        ag.ejecutar(self.api, NoRetirado(), {"id": 12, "tipo": "enrolar", "sensor_id": 13, "timeout_segundos": 90})
        self.assertEqual(LaravelFalso.resultados[12]["mensaje"], "No se detectó que retiraras el dedo.")

    def test_borrar(self):
        lector = s.SensorSimulado(1)
        lector.guardadas.add(20)
        ag.ejecutar(self.api, lector, {"id": 7, "tipo": "borrar", "sensor_id": 20})
        self.assertNotIn(20, lector.guardadas)
        self.assertTrue(LaravelFalso.resultados[7]["exito"])

    def test_error_del_sensor(self):
        class Roto:
            def leer_huella(self, t, avisar, latido): raise s.ErrorSensor("el lector no respondió")
        ag.ejecutar(self.api, Roto(), {"id": 8, "tipo": "verificar", "timeout_segundos": 5})
        self.assertIn("Error del lector", LaravelFalso.resultados[8]["mensaje"])

    def test_accion_desconocida(self):
        ag.ejecutar(self.api, s.SensorSimulado(1), {"id": 10, "tipo": "formatear"})
        self.assertFalse(LaravelFalso.resultados[10]["exito"])


if __name__ == "__main__":
    unittest.main()
