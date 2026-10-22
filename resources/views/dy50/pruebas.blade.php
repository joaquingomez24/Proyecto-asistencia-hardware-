<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pruebas DY50</title>
    <style>
        body { font-family: system-ui, Arial, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; }
        h1 { font-size: 1.4rem; }
        #estado { padding: .6rem .8rem; border-radius: 6px; background: #eef; margin: 1rem 0; }
        #estado.error { background: #fdd; }
        #ultimo { font-size: 1.1rem; margin: 1rem 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #ddd; padding: .4rem .6rem; text-align: left; }
        th { background: #f4f4f4; }
        tr.nueva td { background: #e8f8e8; }
    </style>
</head>
<body>
    <h1>Pruebas del lector DY50</h1>
    <div id="estado">Esperando datos…</div>
    <div id="ultimo"></div>

    <table>
        <thead>
            <tr><th>#</th><th>ID de huella</th><th>Puntaje</th><th>Lector</th><th>Recibido</th></tr>
        </thead>
        <tbody id="filas"></tbody>
    </table>

    <script>
        var URL_EVENTOS = '/api/dy50/eventos';   // relativa: sirve por http y por https
        var ultimoVisto = 0;
        var primeraCarga = true;

        function celda(texto) {
            var td = document.createElement('td');
            td.textContent = texto;
            return td;
        }

        function pintar(eventos) {
            var cuerpo = document.getElementById('filas');
            cuerpo.textContent = '';
            var maximo = ultimoVisto;
            eventos.forEach(function (e) {
                var tr = document.createElement('tr');
                if (!primeraCarga && e.id > ultimoVisto) { tr.className = 'nueva'; }
                tr.appendChild(celda(e.id));
                tr.appendChild(celda(e.fingerprint_id));
                tr.appendChild(celda(e.confidence));
                tr.appendChild(celda(e.device || '-'));
                tr.appendChild(celda(e.created_at));
                cuerpo.appendChild(tr);
                if (e.id > maximo) { maximo = e.id; }
            });
            ultimoVisto = maximo;
            primeraCarga = false;

            var ultimo = document.getElementById('ultimo');
            if (eventos.length === 0) {
                ultimo.textContent = 'Todavía no se recibió ningún evento.';
            } else {
                var u = eventos[0];
                ultimo.textContent = 'Último evento: huella ID ' + u.fingerprint_id +
                    ' (puntaje ' + u.confidence + ') — ' + u.created_at;
            }
        }

        function actualizar() {
            var estado = document.getElementById('estado');
            fetch(URL_EVENTOS, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                .then(function (r) {
                    if (!r.ok) { throw new Error('HTTP ' + r.status); }
                    return r.json();
                })
                .then(function (datos) {
                    pintar(datos.eventos || []);
                    estado.className = '';
                    estado.textContent = 'Actualizado ' + new Date().toLocaleTimeString() + ' (cada 2 segundos)';
                })
                .catch(function (e) {
                    estado.className = 'error';
                    estado.textContent = 'No se pudo leer la lista de eventos: ' + e.message;
                })
                .then(function () { setTimeout(actualizar, 2000); });
        }

        actualizar();
    </script>
</body>
</html>
