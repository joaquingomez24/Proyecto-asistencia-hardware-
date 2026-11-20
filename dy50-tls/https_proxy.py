#!/usr/bin/env python3
"""Proxy HTTPS de desarrollo para reenviar /api/dy50/* a Laravel local.

El TLS termina aquí. La conexión siguiente es HTTP a 127.0.0.1, dentro de la PC.
No usar como servidor de producción.
"""
import argparse
import json
import ssl
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.request import Request, urlopen
from urllib.error import HTTPError, URLError

LARAVEL_BASE = "http://127.0.0.1:8000"


class ProxyHandler(BaseHTTPRequestHandler):
    def send_json_error(self, status, message):
        data = json.dumps({"mensaje": message}, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)

    def forward(self):
        if not self.path.startswith("/api/dy50/"):
            self.send_json_error(404, "Este proxy solo admite /api/dy50/")
            return

        if self.command not in ("GET", "POST"):
            self.send_json_error(405, "Metodo no permitido")
            return

        body = None
        if self.command == "POST":
            try:
                length = int(self.headers.get("Content-Length", "0"))
            except ValueError:
                self.send_json_error(400, "Content-Length invalido")
                return
            if length < 0 or length > 1024 * 1024:
                self.send_json_error(413, "Cuerpo demasiado grande")
                return
            body = self.rfile.read(length)

        headers = {}
        for name in ("Content-Type", "Accept", "Authorization"):
            if self.headers.get(name):
                headers[name] = self.headers[name]

        request = Request(
            LARAVEL_BASE + self.path,
            data=body,
            headers=headers,
            method=self.command,
        )

        try:
            with urlopen(request, timeout=10) as response:
                status = response.status
                data = response.read()
                content_type = response.headers.get(
                    "Content-Type", "application/json"
                )
        except HTTPError as error:
            status = error.code
            data = error.read()
            content_type = error.headers.get(
                "Content-Type", "application/json"
            )
        except (URLError, TimeoutError, OSError) as error:
            print("No se pudo conectar con Laravel:", error)
            self.send_json_error(502, "No se pudo conectar con Laravel en 127.0.0.1:8000")
            return

        self.send_response(status)
        self.send_header("Content-Type", content_type)
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)

    do_GET = forward
    do_POST = forward

    def log_message(self, fmt, *args):
        print("[HTTPS]", self.address_string(), "-", fmt % args)


def main():
    parser = argparse.ArgumentParser(description="Proxy HTTPS local para API DY50/Laravel")
    parser.add_argument("--cert", required=True, help="Ruta al server.crt")
    parser.add_argument("--key", required=True, help="Ruta al server.key (privada)")
    parser.add_argument("--host", default="0.0.0.0", help="Interfaz; predeterminado 0.0.0.0")
    parser.add_argument("--port", type=int, default=8443, help="Puerto HTTPS; predeterminado 8443")
    args = parser.parse_args()

    server = ThreadingHTTPServer((args.host, args.port), ProxyHandler)
    context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
    context.minimum_version = ssl.TLSVersion.TLSv1_2
    context.load_cert_chain(certfile=args.cert, keyfile=args.key)
    server.socket = context.wrap_socket(server.socket, server_side=True)
    print(f"HTTPS DY50 activo en https://{args.host}:{args.port}")
    print("Reenvia solo /api/dy50/* a", LARAVEL_BASE)
    print("Presiona Ctrl+C para detenerlo.")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("Cerrando proxy HTTPS...")
    finally:
        server.server_close()


if __name__ == "__main__":
    main()
