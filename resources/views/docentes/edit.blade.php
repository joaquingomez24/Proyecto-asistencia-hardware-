@extends('layouts.app')

@section('title', 'Editar docente')

@section('content')
<div class="card-consulta">
    <div class="header-consulta">
        <div class="icono-box"><i class="fa-solid fa-user-pen"></i></div>
        <div>
            <h2>Editar docente</h2>
            <p>{{ $docente->nombre_completo }} — DNI {{ $docente->dni }}{{ $docente->activo ? '' : ' (inactivo)' }}</p>
        </div>
    </div>

    <x-errores />

    {{-- Huellas: se registran en el lector DY50 conectado a la Raspberry. --}}
    <section class="panel-huellas" id="panel-huellas"
             data-url-enrolar="{{ route('docentes.huellas.enrolar', $docente) }}"
             data-url-estado="{{ url('/lector/comandos') }}"
             data-url-en-linea="{{ route('lector.en-linea') }}">
        <h3 class="subtitulo"><i class="fa-solid fa-fingerprint"></i> Huellas</h3>

        @forelse ($docente->huellas as $huella)
            <div class="huella-item">
                <span>Posición <strong>{{ $huella->sensor_id }}</strong> en el lector{{ $huella->dedo ? ' · ' . $huella->dedo : '' }}</span>
                <form method="POST" action="{{ route('docentes.huellas.destroy', [$docente, $huella]) }}"
                      onsubmit="return confirm('¿Desvincular esta huella? El docente ya no podrá registrar asistencia con ese dedo.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-peligro">Desvincular</button>
                </form>
            </div>
        @empty
            <p class="ayuda">Este docente todavía no tiene huellas registradas.</p>
        @endforelse

        <div class="fila fila-enrolar">
            <div class="campo">
                <label for="dedo">Dedo a registrar (opcional)</label>
                <input id="dedo" type="text" maxlength="40" placeholder="Ej.: índice derecho">
            </div>
            <div class="campo campo-quitar">
                <button type="button" id="btn-enrolar"><i class="fa-solid fa-hand-pointer"></i> Registrar huella en el lector</button>
            </div>
        </div>
        <p class="ayuda" id="estado-lector-enrolar">Verificando conexión con el lector…</p>
    </section>

    @include('docentes._form', [
        'docente' => $docente,
        'accion' => route('docentes.update', $docente),
        'metodo' => 'PUT',
        'boton' => 'Guardar cambios',
    ])
</div>

{{-- Ventana de progreso del registro de huella --}}
<div id="modal-lector" class="modal-lector" hidden>
    <div class="modal-lector-caja">
        <div id="modal-lector-icono" class="modal-lector-icono"><span class="spinner"></span></div>
        <h3 id="modal-lector-titulo">Registrando huella</h3>
        <p id="modal-lector-texto">Esperando al lector…</p>
        <div class="acciones" style="justify-content: center;">
            <button type="button" id="modal-lector-cancelar" class="btn-secundario">Cancelar</button>
            <button type="button" id="modal-lector-cerrar" hidden>Aceptar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/lector.js') }}"></script>
    <script src="{{ asset('js/enrolar-huella.js') }}"></script>
@endpush
