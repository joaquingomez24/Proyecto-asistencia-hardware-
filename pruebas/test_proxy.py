"""Prueba el proxy HTTPS en modo 'api': solo deben pasar las rutas del agente."""
import json
import os
import socket
import ssl
import subprocess
import sys
import tempfile
import threading
import time
import unittest
import urllib.error
import urllib.request
from http.server import BaseHTTPRequestHandler, HTTPServer

RAIZ = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
PROXY = os.path.join(RAIZ, "https_proxy.py")


class Destino(BaseHTTPRequestHandler):
    vistos = []

    def log_message(self, *a):
        pass

    def _responder(self):
        largo = int(self.headers.get("Content-Length") or 0)
        cuerpo = self.rfile.read(largo) if largo else b""
        Destino.vistos.append((self.command, self.path, self.headers.get("Authorization"), cuerpo))
        datos = json.dumps({"ok": True}).encode()
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(datos)))
        self.end_headers()
        self.wfile.write(datos)

    do_GET = do_POST = _responder


def puerto_libre():
    with socket.socket() as sck:
        sck.bind(("127.0.0.1", 0))
        return sck.getsockname()[1]


class PruebasProxy(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.tmp = tempfile.TemporaryDirectory()
        cert, key = os.path.join(cls.tmp.name, "server.crt"), os.path.join(cls.tmp.name, "server.key")
        subprocess.run(["openssl", "req", "-x509", "-newkey", "rsa:2048", "-nodes", "-days", "1", "-subj", "/CN=localhost",
                        "-keyout", key, "-out", cert], check=True, capture_output=True)

        cls.puerto_destino = puerto_libre()
        cls.destino = HTTPServer(("127.0.0.1", cls.puerto_destino), Destino)
        threading.Thread(target=cls.destino.serve_forever, daemon=True).start()

        cls.puerto = puerto_libre()
        cls.proc = subprocess.Popen([sys.executable, PROXY, "--cert", cert, "--key", key, "--host", "127.0.0.1",
                                     "--port", str(cls.puerto), "--backend", "127.0.0.1:%d" % cls.puerto_destino,
                                     "--modo", "api"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        for _ in range(50):
            try:
                socket.create_connection(("127.0.0.1", cls.puerto), timeout=0.2).close()
                break
            except OSError:
                time.sleep(0.1)

        cls.ctx = ssl.create_default_context()
        cls.ctx.check_hostname = False
        cls.ctx.verify_mode = ssl.CERT_NONE

    @classmethod
    def tearDownClass(cls):
        cls.proc.terminate()
        cls.proc.wait(timeout=5)
        cls.destino.shutdown()
        cls.tmp.cleanup()

    def pedir(self, metodo, ruta, cuerpo=None):
        datos = json.dumps(cuerpo).encode() if cuerpo is not None else None
        req = urllib.request.Request("https://127.0.0.1:%d%s" % (self.puerto, ruta), data=datos, method=metodo)
        req.add_header("Authorization", "Bearer abc")
        if datos is not None:
            req.add_header("Content-Type", "application/json")
        try:
            with urllib.request.urlopen(req, context=self.ctx, timeout=5) as r:
                return r.status
        except urllib.error.HTTPError as e:
            return e.code

    def setUp(self):
        Destino.vistos.clear()

    def test_pasan_las_rutas_del_agente(self):
        self.assertEqual(self.pedir("GET", "/api/dy50/comandos/siguiente?dispositivo=raspberry-1"), 200)
        self.assertEqual(self.pedir("POST", "/api/dy50/comandos/12/progreso", {"mensaje": "hola"}), 200)
        self.assertEqual(self.pedir("POST", "/api/dy50/comandos/12/resultado", {"exito": True}), 200)
        self.assertEqual(len(Destino.vistos), 3)
        self.assertEqual(Destino.vistos[0][2], "Bearer abc")          # el token llega a Laravel
        self.assertEqual(json.loads(Destino.vistos[2][3]), {"exito": True})

    def test_no_pasa_nada_mas(self):
        casos = [
            ("GET", "/login"),
            ("GET", "/docentes"),
            ("POST", "/login"),
            ("GET", "/api/dy50/eventos"),
            ("POST", "/api/dy50/eventos"),
            ("GET", "/api/dy50/comandos/12/resultado"),
            ("POST", "/api/dy50/comandos/siguiente"),
            ("POST", "/api/dy50/comandos/abc/progreso"),
            ("POST", "/api/dy50/comandos/12/progreso/../../../login"),
        ]
        for metodo, ruta in casos:
            with self.subTest(metodo=metodo, ruta=ruta):
                self.assertEqual(self.pedir(metodo, ruta, {} if metodo == "POST" else None), 404)
        self.assertEqual(Destino.vistos, [])


if __name__ == "__main__":
    unittest.main()
