{{--
    Formulario compartido de alta y edición de docentes.
    Variables: $docente (Docente, nuevo o existente), $dias (DiaSemana[]), $accion (URL), $metodo (null | 'PUT'), $boton (texto)
--}}
@php
    // Filas de horarios: lo que se envió (si hubo error de validación) o lo guardado.
    $filas = old('horarios');
    if ($filas === null) {
        $filas = $docente->exists
            ? $docente->horarios->map(fn ($h) => [
                'id' => $h->id,
                'materia' => $h->materia->nombre,
                'turno' => $h->turno,
                'curso' => $h->curso,
                'division' => $h->division,
                'dia_semana' => $h->dia_semana->value,
                'hora_inicio' => substr((string) $h->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $h->hora_fin, 0, 5),
            ])->all()
            : [];
    }
    $opcionesCurso = ['1°', '2°', '3°', '4°', '5°', '6°'];
@endphp

<form method="POST" action="{{ $accion }}">
    @csrf
    @if ($metodo) @method($metodo) @endif

    <div class="fila">
        <div class="campo">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" type="text" maxlength="50" required value="{{ old('nombre', $docente->nombre) }}">
        </div>
        <div class="campo">
            <label for="apellido">Apellido</label>
            <input id="apellido" name="apellido" type="text" maxlength="50" required value="{{ old('apellido', $docente->apellido) }}">
        </div>
        <div class="campo">
            <label for="dni">DNI</label>
            <input id="dni" name="dni" type="text" inputmode="numeric" maxlength="10" required value="{{ old('dni', $docente->dni) }}">
        </div>
    </div>

    <div class="fila">
        <div class="campo">
            <label for="telefono">Teléfono</label>
            <input id="telefono" name="telefono" type="text" maxlength="20" value="{{ old('telefono', $docente->telefono) }}">
        </div>
        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" maxlength="100" value="{{ old('email', $docente->email) }}">
        </div>
    </div>

    <h3 class="subtitulo">Horarios</h3>
    <p class="ayuda">Cada horario es una materia que dicta en un curso, un día y una franja. Lo que quede en esta lista es lo que se guarda: los horarios quitados dejan de aparecer, pero se conserva su historial de asistencias.</p>

    <div id="contenedor-horarios" data-filas="{{ json_encode($filas) }}"></div>

    <div class="acciones">
        <button type="button" id="btn-agregar" class="btn-secundario"><i class="fa-solid fa-plus"></i> Agregar horario</button>
        <button type="submit">{{ $boton }}</button>
        <a href="{{ route('docentes.index') }}" class="enlace-suave">Cancelar</a>
    </div>
</form>

{{-- Molde de una fila de horario; horarios-form.js lo copia y le pone los nombres horarios[n][campo]. --}}
<template id="plantilla-horario">
    <div class="bloque-horario">
        <input type="hidden" data-campo="id">
        <div class="fila">
            <div class="campo">
                <label>Materia</label>
                <input type="text" data-campo="materia" maxlength="70" required>
            </div>
            <div class="campo">
                <label>Turno</label>
                <select data-campo="turno" required>
                    <option value="Mañana">Mañana</option>
                    <option value="Tarde">Tarde</option>
                    <option value="Vespertino">Vespertino</option>
                </select>
            </div>
            <div class="campo">
                <label>Curso</label>
                <select data-campo="curso" required>
                    @foreach ($opcionesCurso as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach
                </select>
            </div>
            <div class="campo">
                <label>División</label>
                <select data-campo="division" required>
                    @foreach ($opcionesCurso as $d) <option value="{{ $d }}">{{ $d }}</option> @endforeach
                </select>
            </div>
        </div>
        <div class="fila">
            <div class="campo">
                <label>Día</label>
                <select data-campo="dia_semana" required>
                    @foreach ($dias as $dia) <option value="{{ $dia->value }}">{{ $dia->etiqueta() }}</option> @endforeach
                </select>
            </div>
            <div class="campo">
                <label>Entrada</label>
                <input type="time" data-campo="hora_inicio" required>
            </div>
            <div class="campo">
                <label>Salida</label>
                <input type="time" data-campo="hora_fin" required>
            </div>
            <div class="campo campo-quitar">
                <button type="button" class="btn-peligro" data-quitar><i class="fa-solid fa-trash"></i> Quitar</button>
            </div>
        </div>
    </div>
</template>

@push('scripts')
    <script src="{{ asset('js/horarios-form.js') }}"></script>
@endpush
