<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi asistencia | EPET 20</title>
    <style>
        :root { font-family: system-ui, Arial, sans-serif; color: #171d28; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f5f8; }
        header { display: flex; justify-content: space-between; gap: 1rem; align-items: center; padding: 1rem max(1rem, calc((100vw - 960px)/2)); background: #17243b; color: #fff; }
        header h1 { font-size: 1.2rem; margin: 0; }
        header form { margin: 0; }
        header button { color: #fff; border: 1px solid #8e9bb0; background: transparent; padding: .5rem .75rem; border-radius: 8px; cursor: pointer; }
        main { width: min(960px, 100%); margin: 1.5rem auto; padding: 0 1rem 2rem; }
        .panel { padding: 1.2rem; margin-bottom: 1rem; background: #fff; border: 1px solid #dce1e8; border-radius: 12px; }
        h2 { margin: 0 0 .8rem; font-size: 1.1rem; }
        .muted { color: #596579; }
        .status { padding: .8rem 1rem; border-radius: 8px; background: #eef2f8; margin: .7rem 0; }
        .status.error { background: #fff0f0; color: #8b1c1c; }
        .status.success { background: #e9f8ee; color: #14532d; }
        label { display: block; margin: .9rem 0 .35rem; font-weight: 650; }
        select { width: 100%; max-width: 100%; padding: .75rem; border: 1px solid #bec7d4; border-radius: 8px; font: inherit; }
        button.primary { margin-top: 1rem; padding: .8rem 1rem; border: 0; border-radius: 8px; background: #17243b; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        button.primary:disabled { opacity: .5; cursor: not-allowed; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .7rem .4rem; border-bottom: 1px solid #e2e7ee; }
        th { font-size: .9rem; color: #4a5568; }
        .tag { display: inline-block; padding: .2rem .5rem; border-radius: 6px; background: #eef2f8; font-size: .86rem; }
        @media (max-width: 640px) { table { font-size: .9rem; } th, td { padding: .55rem .2rem; } }
    </style>
</head>
<body>
<header>
    <h1>Asistencia EPET 20</h1>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
</header>
<main>
    <section class="panel">
        <h2>Hola, {{ $docente->nombre }} {{ $docente->apellido }}</h2>
        <p class="muted">Hoy es {{ $ahora->locale('es')->isoFormat('dddd D [de] MMMM') }}. Elegí la materia correspondiente y confirmá con una lectura nueva del DY50.</p>
    </section>

    @if (session('exito'))
        <div class="status success" role="status">{{ session('exito') }}</div>
    @endif
    @if ($errors->any())
        <div class="status error" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="panel">
        <h2>Estado del lector</h2>
        <div id="estado-lector" class="status" aria-live="polite">Esperando una nueva lectura del DY50…</div>
        <p class="muted">Dejá abierta esta pantalla, apoyá el dedo en el lector y esperá a que aparezca la lectura. La página solo aceptará eventos nuevos recibidos después de abrirse.</p>

        <form method="POST" action="{{ route('docente.asistencia.store') }}" id="form-asistencia">
            @csrf
            <input type="hidden" id="codigo_huella" name="codigo_huella" value="">
            <input type="hidden" id="dy50_event_id" name="dy50_event_id" value="">

            <label for="id_materia">Materia / horario</label>
            <select id="id_materia" name="id_materia" required>
                <option value="">Seleccioná una materia de hoy</option>
                @forelse ($materiasHoy as $materia)
                    @php $marcada = $asistenciasPorMateria->has($materia->id_materias); @endphp
                    <option value="{{ $materia->id_materias }}" @disabled($marcada)>
                        {{ $materia->nombre }} — {{ substr((string) $materia->horario_inicio, 0, 5) }} a {{ substr((string) $materia->horario_finalizacion, 0, 5) }}
                        @if ($marcada) (ya registrada: {{ $asistenciasPorMateria[$materia->id_materias]->estado }}) @endif
                    </option>
                @empty
                    <option value="" disabled>No tenés materias asignadas para hoy.</option>
                @endforelse
            </select>

            <button class="primary" type="submit" id="btn-marcar" disabled>Confirmar asistencia con esta lectura</button>
        </form>
    </section>

    <section class="panel">
        <h2>Mis materias de hoy</h2>
        @if ($materiasHoy->isEmpty())
            <p class="muted">No hay materias asignadas para hoy. Si esto no coincide con tu horario, consultá a Secretaría.</p>
        @else
            <table>
                <thead><tr><th>Materia</th><th>Horario</th><th>Estado</th></tr></thead>
                <tbody>
                @foreach ($materiasHoy as $materia)
                    @php $registro = $asistenciasPorMateria->get($materia->id_materias); @endphp
                    <tr>
                        <td>{{ $materia->nombre }}</td>
                        <td>{{ substr((string) $materia->horario_inicio, 0, 5) }}–{{ substr((string) $materia->horario_finalizacion, 0, 5) }}</td>
                        <td>@if ($registro)<span class="tag">{{ $registro->estado }}</span>@else<span class="tag">Sin registrar</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
</main>
<script>
(() => {
    const urlEventos = '/api/dy50/eventos';
    let ultimoEventoId = Number(@json($ultimoEventoId));
    let lecturaActual = null;

    const estado = document.getElementById('estado-lector');
    const codigo = document.getElementById('codigo_huella');
    const eventoId = document.getElementById('dy50_event_id');
    const boton = document.getElementById('btn-marcar');
    const materia = document.getElementById('id_materia');

    function actualizarBoton() {
        boton.disabled = !lecturaActual || !materia.value;
    }

    materia.addEventListener('change', actualizarBoton);

    async function consultarEventos() {
        try {
            const respuesta = await fetch(urlEventos, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!respuesta.ok) throw new Error('HTTP ' + respuesta.status);

            const datos = await respuesta.json();
            const eventos = Array.isArray(datos.eventos) ? datos.eventos : [];
            const nuevos = eventos
                .filter(e => Number(e.id) > ultimoEventoId)
                .sort((a, b) => Number(a.id) - Number(b.id));

            if (nuevos.length > 0) {
                const evento = nuevos[nuevos.length - 1];
                ultimoEventoId = Number(evento.id);
                lecturaActual = evento;
                codigo.value = String(evento.fingerprint_id);
                eventoId.value = String(evento.id);
                estado.className = 'status success';
                estado.textContent = 'Lectura recibida: ID ' + evento.fingerprint_id +
                    ', puntaje ' + evento.confidence + '. Elegí la materia y confirmá.';
            }
        } catch (error) {
            estado.className = 'status error';
            estado.textContent = 'No se pudo consultar el lector: ' + error.message;
        } finally {
            actualizarBoton();
            window.setTimeout(consultarEventos, 2000);
        }
    }

    actualizarBoton();
    consultarEventos();
})();
</script>
</body>
</html>
