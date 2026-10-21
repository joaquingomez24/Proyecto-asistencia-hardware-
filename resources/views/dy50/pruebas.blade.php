<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pruebas DY50</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, Arial, sans-serif; background: #111827; color: #e5e7eb; }
        main { max-width: 1000px; margin: 40px auto; padding: 0 18px; }
        h1 { margin-bottom: 8px; }
        .sub { color: #9ca3af; margin-top: 0; }
        .panel { background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 20px; margin-top: 18px; }
        .status { font-weight: 600; }
        .ok { color: #86efac; }
        .bad { color: #fca5a5; }
        .stats { display: flex; gap: 12px; flex-wrap: wrap; }
        .stat { flex: 1; min-width: 180px; background: #111827; border-radius: 8px; padding: 14px; }
        .label { color: #9ca3af; font-size: .9rem; }
        .value { font-size: 1.5rem; margin-top: 5px; font-weight: 700; overflow-wrap: anywhere; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 10px; border-bottom: 1px solid #374151; text-align: left; white-space: nowrap; }
        th { color: #9ca3af; font-size: .85rem; }
        .empty { color: #9ca3af; padding: 22px 0; text-align: center; }
        .note { color: #9ca3af; font-size: .9rem; line-height: 1.5; }
    </style>
</head>
<body>
<main>
    <h1>Pruebas de conexión del DY50</h1>
    <p class="sub">Eventos recibidos por Laravel desde la Raspberry Pi.</p>

    <section class="panel">
        <div id="connectionStatus" class="status">Conectando con Laravel...</div>
        <p class="note">La página consulta automáticamente los últimos eventos cada 2 segundos. En Thonny, usa la opción 6 (Buscar una huella) con un dedo que ya esté registrado en el DY50.</p>
        <div class="stats">
            <div class="stat"><div class="label">Último ID de huella recibido</div><div class="value" id="lastFingerprint">—</div></div>
            <div class="stat"><div class="label">Puntaje de coincidencia</div><div class="value" id="lastConfidence">—</div></div>
            <div class="stat"><div class="label">Hora del último evento</div><div class="value" id="lastTime">—</div></div>
        </div>
    </section>

    <section class="panel">
        <h2>Eventos recientes</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID evento</th><th>ID de huella DY50</th><th>Confianza</th><th>Recibido en</th></tr></thead>
                <tbody id="eventsBody"><tr><td class="empty" colspan="4">Esperando el primer evento…</td></tr></tbody>
            </table>
        </div>
    </section>
</main>
<script>
    const endpoint = '/api/dy50/eventos';
    const statusEl = document.getElementById('connectionStatus');
    const bodyEl = document.getElementById('eventsBody');

    function formatDate(value) {
        if (!value) return '—';
        const date = new Date(value.replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
    }

    function cell(text) {
        const td = document.createElement('td');
        td.textContent = text ?? '—';
        return td;
    }

    async function cargarEventos() {
        try {
            const response = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const eventos = await response.json();
            statusEl.textContent = 'Conectado con Laravel · actualización automática activa';
            statusEl.className = 'status ok';

            if (!eventos.length) {
                bodyEl.replaceChildren();
                const row = document.createElement('tr');
                const td = cell('Todavía no se recibió ninguna huella reconocida.');
                td.colSpan = 4;
                td.className = 'empty';
                row.appendChild(td);
                bodyEl.appendChild(row);
                document.getElementById('lastFingerprint').textContent = '—';
                document.getElementById('lastConfidence').textContent = '—';
                document.getElementById('lastTime').textContent = '—';
                return;
            }

            const latest = eventos[0];
            document.getElementById('lastFingerprint').textContent = latest.fingerprint_id;
            document.getElementById('lastConfidence').textContent = latest.confidence ?? '—';
            document.getElementById('lastTime').textContent = formatDate(latest.created_at);

            const fragment = document.createDocumentFragment();
            for (const evento of eventos) {
                const row = document.createElement('tr');
                row.appendChild(cell(evento.id));
                row.appendChild(cell(evento.fingerprint_id));
                row.appendChild(cell(evento.confidence));
                row.appendChild(cell(formatDate(evento.created_at)));
                fragment.appendChild(row);
            }
            bodyEl.replaceChildren(fragment);
        } catch (error) {
            statusEl.textContent = `No se pudo consultar Laravel: ${error.message}`;
            statusEl.className = 'status bad';
        }
    }

    cargarEventos();
    window.setInterval(cargarEventos, 2000);
</script>
</body>
</html>
