@extends('layouts.app')

@section('title', 'Consulta de asistencias')

@section('content')
<div class="card-consulta">
    <div class="header-consulta">
        <div class="icono-box"><i class="fa-solid fa-clipboard-list"></i></div>
        <div>
            <h2>Consulta de asistencias</h2>
            <p>Listado de asistencias registradas. Podés filtrar por fecha o por docente.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('asistencias.index') }}" class="filtros-box">
        <div class="campo-filtro">
            <label for="fecha">Fecha</label>
            <input id="fecha" type="date" name="fecha" value="{{ request('fecha') }}">
        </div>
        <div class="campo-filtro">
            <label for="docente">Docente</label>
            <input id="docente" type="text" name="docente" placeholder="Nombre o apellido" value="{{ request('docente') }}">
        </div>
        <button type="submit" class="btn-buscar"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        @if (request()->hasAny(['fecha', 'docente']))
            <a href="{{ route('asistencias.index') }}" class="enlace-suave">Limpiar</a>
        @endif
    </form>

    <x-errores />

    <div class="tabla-scroll">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Docente</th>
                    <th>Materia</th>
                    <th>Curso</th>
                    <th>Entrada</th>
                    <th>Salida</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($asistencias as $asistencia)
                <tr>
                    <td>{{ $asistencia->fecha->format('d/m/Y') }}</td>
                    <td>{{ $asistencia->docente->nombre_completo }}</td>
                    <td>{{ $asistencia->horario->materia->nombre }}</td>
                    <td>{{ $asistencia->horario->curso }} {{ $asistencia->horario->division }}</td>
                    <td>{{ $asistencia->hora_ingreso ? substr($asistencia->hora_ingreso, 0, 5) : '—' }}</td>
                    <td>{{ $asistencia->hora_egreso ? substr($asistencia->hora_egreso, 0, 5) : '—' }}</td>
                    <td><span class="badge-estado {{ $asistencia->estado->value }}">{{ $asistencia->estado->etiqueta() }}</span></td>
                    <td>
                        <a href="{{ route('asistencias.edit', $asistencia) }}" class="btn-editar">
                            <i class="fa-solid fa-pen"></i> Editar
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align: center; color: #64748b;">No hay asistencias para mostrar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($asistencias->hasPages())
        <div class="paginacion">
            @if ($asistencias->onFirstPage())
                <span class="deshabilitado">« Anterior</span>
            @else
                <a href="{{ $asistencias->previousPageUrl() }}">« Anterior</a>
            @endif

            <span>Página {{ $asistencias->currentPage() }} de {{ $asistencias->lastPage() }}</span>

            @if ($asistencias->hasMorePages())
                <a href="{{ $asistencias->nextPageUrl() }}">Siguiente »</a>
            @else
                <span class="deshabilitado">Siguiente »</span>
            @endif
        </div>
    @endif
</div>
@endsection
