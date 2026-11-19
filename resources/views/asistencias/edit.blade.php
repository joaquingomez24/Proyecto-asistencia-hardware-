@extends('layouts.app')

@section('title', 'Editar asistencia')

@section('content')
<div class="card-consulta card-editar">
    <div class="header-consulta">
        <div class="icono-box"><i class="fa-solid fa-pen-to-square"></i></div>
        <div>
            <h2>Editar asistencia</h2>
            <p>{{ $asistencia->docente->nombre_completo }} — {{ $asistencia->fecha->format('d/m/Y') }}</p>
        </div>
    </div>

    <x-errores />

    <form action="{{ route('asistencias.update', $asistencia) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="fila">
            <div class="campo">
                <label for="horario_id">Materia</label>
                <select name="horario_id" id="horario_id" required>
                    @foreach ($horarios as $horario)
                        <option value="{{ $horario->id }}" @selected((int) old('horario_id', $asistencia->horario_id) === $horario->id)>
                            {{ $horario->descripcion() }} · {{ $horario->dia_semana->etiqueta() }} {{ $horario->rango() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="fila">
            <div class="campo">
                <label for="hora_ingreso">Hora de entrada</label>
                <input type="time" step="1" name="hora_ingreso" id="hora_ingreso"
                       value="{{ old('hora_ingreso', $asistencia->hora_ingreso) }}">
            </div>
            <div class="campo">
                <label for="hora_egreso">Hora de salida</label>
                <input type="time" step="1" name="hora_egreso" id="hora_egreso"
                       value="{{ old('hora_egreso', $asistencia->hora_egreso) }}">
            </div>
            <div class="campo">
                <label for="estado">Estado</label>
                <select name="estado" id="estado" required>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}" @selected(old('estado', $asistencia->estado->value) === $estado->value)>
                            {{ $estado->etiqueta() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="acciones">
            <button type="submit">Guardar cambios</button>
            <a href="{{ route('asistencias.index') }}" class="enlace-suave">Cancelar</a>
        </div>
    </form>
</div>
@endsection
