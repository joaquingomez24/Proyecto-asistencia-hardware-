@extends('layouts.docente')

@section('title', 'Mi asistencia')

@section('content')
<div id="panel"
     data-url-iniciar="{{ route('docente.asistencia.iniciar') }}"
     data-url-estado="{{ url('/lector/comandos') }}"
     data-url-en-linea="{{ route('lector.en-linea') }}"
     data-login-url="{{ route('login') }}">

    <section class="tarjeta">
        <h1>Hola, {{ $docente->nombre }} {{ $docente->apellido }}</h1>
        <p class="suave">{{ $ahora->locale('es')->isoFormat('dddd D [de] MMMM') }}. Elegí la materia que vas a dictar y registrá tu asistencia con tu huella.</p>
        <p id="estado-lector" class="estado-lector" aria-live="polite">Verificando conexión con el lector…</p>
    </section>

    @unless ($tieneHuella)
        <div class="aviso aviso-error">Todavía no tenés una huella registrada. Consultá a Secretaría.</div>
    @endunless

    <section class="tarjeta">
        <h2>Materias de hoy</h2>

        @forelse ($horarios as $horario)
            @php $situacion = $situaciones[$horario->id]; @endphp
            <button type="button"
                    class="horario {{ $situacion['registrable'] ? '' : 'horario-bloqueado' }}"
                    data-id="{{ $horario->id }}"
                    data-nombre="{{ $horario->materia->nombre }}"
                    @disabled(! $situacion['registrable'] || ! $tieneHuella)>
                <span class="horario-materia">{{ $horario->materia->nombre }}</span>
                <span class="horario-detalle">{{ $horario->curso }} {{ $horario->division }} · {{ $horario->turno }} · {{ $horario->rango() }}</span>
                <span class="horario-situacion">{{ $situacion['texto'] }}</span>
            </button>
        @empty
            <p class="suave">No tenés materias asignadas para hoy. Si esto no coincide con tu horario, consultá a Secretaría.</p>
        @endforelse
    </section>

    <div class="barra-accion" id="barra-accion" hidden>
        <span id="seleccion-texto"></span>
        <button type="button" id="btn-registrar" class="btn-grande">Registrar asistencia</button>
    </div>
</div>

{{-- Ventana que acompaña la lectura de la huella --}}
<div id="superposicion" class="superposicion" hidden>
    <div class="superposicion-caja">
        <div id="sup-icono" class="sup-icono"><span class="spinner"></span></div>
        <h2 id="sup-titulo">Apoyá tu dedo en el lector</h2>
        <p id="sup-texto">Esperando al lector…</p>
        <p id="sup-cuenta" class="suave"></p>
        <div class="acciones-sup">
            <button type="button" id="sup-cancelar" class="btn-secundario">Cancelar</button>
            <button type="button" id="sup-reintentar" class="btn-grande" hidden>Reintentar</button>
            <button type="button" id="sup-cerrar" class="btn-secundario" hidden>Volver</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/lector.js') }}"></script>
    <script src="{{ asset('js/docente-panel.js') }}"></script>
@endpush
