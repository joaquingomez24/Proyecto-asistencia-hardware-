@extends('layouts.app')

@section('title', 'Editar Asistencia')

@section('content')
<div class="card-consulta card-editar">
    <div class="header-consulta">
        <div class="icono-box">
            <i class="fa-solid fa-pen-to-square"></i>
        </div>
        <div>
            <h2>Editar Asistencia</h2>
            <p>{{ $asistencia->nombre_docente }} - {{ $asistencia->materia }}</p>
        </div>
    </div>

    <form action="{{ route('asistencia.update', $asistencia->id_asistencia) }}" method="POST" id="form-editar-asistencia">
        @csrf
        @method('PUT')
        <div class="fila">
            <div class="campo">
                <label for="materia">Materia</label>
                <input type="text" name="materia" id="materia" list="lista-materias" value="{{ old('materia', $asistencia->materia) }}" required>
                <datalist id="lista-materias">
                    @if(isset($materias))
                        @foreach($materias as $mat)
                            <option value="{{ $mat->nombre ?? $mat }}">
                        @endforeach
                    @endif
                </datalist>
            </div>
            
            <div class="campo">
                <label>Hora de entrada</label>
                <input type="time" name="entrada" step="1" value="{{ $asistencia->hora_ingreso }}">
            </div>

            <div class="campo">
                <label>Hora de salida</label>
                <input type="time" name="salida" step="1" value="{{ $asistencia->hora_egreso }}">
            </div>
        </div>

        <div class="fila">
            <div class="campo">
                <label>Estado</label>
                <select name="estado" required>
                    <option value="Presente" {{ $asistencia->estado == 'Presente' ? 'selected' : '' }}>Presente</option>
                    <option value="Tarde" {{ $asistencia->estado == 'Tarde' ? 'selected' : '' }}>Tarde</option>
                    <option value="Ausente" {{ $asistencia->estado == 'Ausente' ? 'selected' : '' }}>Ausente</option>
                    <option value="Adelantado" {{ $asistencia->estado == 'Adelantado' ? 'selected' : '' }}>Adelantado</option>
                </select>
            </div>
        </div>
    </form>

    @php
        // Intenta encontrar la clave/ID del docente sin importar cómo se llame en la base de datos
        $idDocente = $asistencia->id_docente 
                  ?? $asistencia->id_docentes 
                  ?? $asistencia->docente_id 
                  ?? ($asistencia->docente->id_docente ?? null);
    @endphp

    <!-- BOTONES DE ACCIÓN PRINCIPALES -->
    <div style="display: flex; gap: 10px; align-items: center; margin-top: 1.5rem; flex-wrap: wrap;">
        <!-- Guardar Cambios de Asistencia -->
        <button type="submit" form="form-editar-asistencia" style="background-color: #1138a6; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer;">
            Guardar cambios
        </button>

        @if($idDocente)
            <!-- Desactivar Docente (Amarillo) -->
            <form action="{{ route('docentes.desactivar', $idDocente) }}" method="POST" style="margin: 0;">
                @csrf
                @method('PATCH')
                <button type="submit" onclick="return confirm('¿Está seguro de que desea desactivar a este docente?')" style="background-color: #ffc107; color: #000; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer;">
                    Desactivar docente
                </button>
            </form>

            <!-- Borrar Docente (Rojo) -->
            <form action="{{ route('docentes.destroy', $idDocente) }}" method="POST" style="margin: 0;">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('¿Está seguro de que desea eliminar a este docente de forma permanente?')" style="background-color: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer;">
                    Borrar docente
                </button>
            </form>
        @endif
    </div>
</div>
@endsection