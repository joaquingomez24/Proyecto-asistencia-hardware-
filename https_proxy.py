#!/usr/bin/env python3
"""
Proxy HTTPS minimo para pruebas del DY50 (solo libreria estandar de Python).

Recibe HTTPS/TLS en --port (por defecto 8443) y reenvia a Laravel por HTTP
sobre 127.0.0.1:8000 (el que levanta `php artisan serve`).

Modos:
  --modo api    (por defecto) SOLO deja pasar las 3 rutas que usa el agente de la Raspberry
                (GET /api/dy50/comandos/siguiente, POST .../{id}/progreso y .../{id}/resultado).
                Todo lo demas recibe 404. Es el modo seguro para una red compartida.
  --modo sitio  Reenvia TODAS las rutas y metodos (para ver la pagina de Laravel
                por HTTPS). Cualquiera que llegue al puerto vera tu sitio.

NO es un servidor de produccion: sirve para desarrollo y pruebas.
"""
import argparse
import http.client
import json
import re
import socket
import ssl
import sys
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

# Rutas (metodo, patron) que el modo "api" deja pasar: exactamente las del agente dy50_agente.py.
RUTAS_API = [
    ("GET",  re.compile(r"^/api/dy50/comandos/siguiente(\?[A-Za-z0-9_.=&%-]*)?$")),
    ("POST", re.compile(r"^/api/dy50/comandos/[0-9]+/progreso$")),
    ("POST", re.compile(r"^/api/dy50/comandos/[0-9]+/resultado$")),
]
MAX_CUERPO_API = 4096            # bytes; un mensaje real pesa menos de 400


def ruta_permitida(metodo, ruta):
    return any(metodo == m and patron.match(ruta) for m, patron in RUTAS_API)
HOP_BY_HOP = {
    "connection", "keep-alive", "proxy-authenticate", "proxy-authorization",
    "te", "trailers", "transfer-encoding", "upgrade", "content-length", "host",
}

CONFIG = {}                      # se completa en main()


def log(msg):
    print("[%s] %s" % (time.strftime("%H:%M:%S"), msg), flush=True)


class Manejador(BaseHTTPRequestHandler):
    server_version = "dy50-proxy"
    sys_version = ""
    timeout = 15                 # segundos para leer la peticion

    # El handshake TLS se hace aqui, en el hilo de cada conexion. Asi un
    # cliente que se conecta y no habla no bloquea a los demas.
    def setup(self):
        self.request.settimeout(10)
        self.request = CONFIG["contexto"].wrap_socket(self.request, server_side=True)
        super().setup()

    def log_message(self, formato, *args):
        log("%s - %s" % (self.client_address[0], formato % args))

    def responder_json(self, codigo, datos):
        cuerpo = json.dumps(datos).encode("utf-8")
        self.send_response(codigo)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(cuerpo)))
        self.send_header("Connection", "close")
        self.end_headers()
        if self.command != "HEAD":
            self.wfile.write(cuerpo)

    def reenviar(self):
        # Primero se lee el cuerpo (si lo hay) y despues se decide; si no, al
        # rechazar una ruta el cliente veria "conexion cerrada" en vez del 404.
        largo = self.headers.get("Content-Length")
        cuerpo = b""
        if self.command in ("POST", "PUT", "PATCH"):
            if largo is None or not largo.isdigit():
                return self.responder_json(411, {"ok": False, "mensaje": "Falta Content-Length"})
            largo = int(largo)
            if CONFIG["modo"] == "api" and largo > MAX_CUERPO_API:
                return self.responder_json(413, {"ok": False, "mensaje": "Cuerpo demasiado grande"})
            cuerpo = self.rfile.read(largo)

        if CONFIG["modo"] == "api":
            if not ruta_permitida(self.command, self.path):
                return self.responder_json(404, {"ok": False, "mensaje": "Ruta no permitida"})

        cabeceras = {k: v for k, v in self.headers.items() if k.lower() not in HOP_BY_HOP}
        host_original = self.headers.get("Host", "")
        cabeceras["Host"] = host_original or "%s:%s" % (CONFIG["bk_host"], CONFIG["bk_port"])
        cabeceras["X-Forwarded-For"] = self.client_address[0]
        cabeceras["X-Forwarded-Proto"] = "https"
        cabeceras["X-Forwarded-Host"] = host_original
        cabeceras["Connection"] = "close"
        if self.command in ("POST", "PUT", "PATCH"):
            cabeceras["Content-Length"] = str(len(cuerpo))

        con = None
        try:
            con = http.client.HTTPConnection(CONFIG["bk_host"], CONFIG["bk_port"], timeout=15)
            con.request(self.command, self.path, body=cuerpo or None, headers=cabeceras)
            resp = con.getresponse()
            datos = resp.read()
        except (OSError, http.client.HTTPException) as e:
            log("ERROR hablando con Laravel (%s:%s): %s" % (CONFIG["bk_host"], CONFIG["bk_port"], e))
            return self.responder_json(502, {
                "ok": False,
                "mensaje": "El proxy no pudo hablar con Laravel. Esta corriendo php artisan serve?",
            })
        finally:
            if con is not None:
                con.close()

        self.send_response(resp.status)
        for nombre, valor in resp.getheaders():
            if nombre.lower() not in HOP_BY_HOP:
                self.send_header(nombre, valor)
        self.send_header("Content-Length", str(len(datos)))
        self.send_header("Connection", "close")
        self.end_headers()
        if self.command != "HEAD":
            self.wfile.write(datos)

    do_GET = do_POST = do_PUT = do_PATCH = do_DELETE = do_HEAD = do_OPTIONS = reenviar


class Servidor(ThreadingHTTPServer):
    daemon_threads = True
    request_queue_size = 16

    def handle_error(self, request, client_address):
        exc = sys.exc_info()[1]
        if isinstance(exc, (ssl.SSLError, ConnectionError, socket.timeout, TimeoutError)):
            log("%s - conexion descartada (%s)" % (client_address[0], exc))
        else:
            super().handle_error(request, client_address)


def main():
    p = argparse.ArgumentParser(description="Proxy HTTPS de pruebas para DY50 + Laravel")
    p.add_argument("--cert", required=True, help="server.crt (o cadena completa)")
    p.add_argument("--key", required=True, help="server.key")
    p.add_argument("--host", default="0.0.0.0", help="interfaz donde escucha (0.0.0.0 = todas)")
    p.add_argument("--port", type=int, default=8443)
    p.add_argument("--backend", default="127.0.0.1:8000", help="donde esta Laravel (host:puerto)")
    p.add_argument("--modo", choices=["api", "sitio"], default="api")
    a = p.parse_args()

    bk_host, _, bk_port = a.backend.partition(":")
    CONFIG.update(modo=a.modo, bk_host=bk_host, bk_port=int(bk_port or 80))

    ctx = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
    ctx.minimum_version = ssl.TLSVersion.TLSv1_2
    try:
        ctx.load_cert_chain(a.cert, a.key)
    except (OSError, ssl.SSLError) as e:
        sys.exit("No pude cargar el certificado/clave: %s\n"
                 "Revisa las rutas y que server.key sea la clave de server.crt." % e)
    CONFIG["contexto"] = ctx

    try:
        srv = Servidor((a.host, a.port), Manejador)
    except OSError as e:
        sys.exit("No pude abrir %s:%s -> %s\n(Puerto ocupado? Otra copia del proxy abierta?)" % (a.host, a.port, e))

    log("Proxy HTTPS escuchando en %s:%s  ->  http://%s:%s   (modo: %s)"
        % (a.host, a.port, bk_host, bk_port, a.modo))
    if a.modo == "api":
        log("Solo se reenvian las rutas del agente: /api/dy50/comandos/siguiente, /{id}/progreso y /{id}/resultado")
    else:
        log("ATENCION: modo sitio, se reenvia TODO Laravel a la red.")
    try:
        srv.serve_forever()
    except KeyboardInterrupt:
        log("Detenido.")


if __name__ == "__main__":
    main()
