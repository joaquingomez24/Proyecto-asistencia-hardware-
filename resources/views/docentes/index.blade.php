@extends('layouts.app')

@section('title', 'Docentes')

@section('content')
<div class="card-consulta">
    <div class="header-consulta">
        <div class="icono-box"><i class="fa-solid fa-user-pen"></i></div>
        <div>
            <h2>Gestión de docentes</h2>
            <p>Buscá por nombre, apellido o DNI para editar sus datos, horarios y huellas.</p>
        </div>
    </div>

    <x-errores />

    <form action="{{ route('docentes.index') }}" method="GET" class="filtros-box">
        <div class="campo-filtro" style="flex: 1;">
            <label for="buscar">Buscar docente</label>
            <input type="text" id="buscar" name="buscar" placeholder="Nombre, apellido o DNI…" value="{{ request('buscar') }}">
        </div>
        <button type="submit" class="btn-buscar"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        <a href="{{ route('docentes.create') }}" class="btn-buscar btn-enlace"><i class="fa-solid fa-user-plus"></i> Nuevo</a>
    </form>

    <div class="tabla-scroll">
        <table>
            <thead>
                <tr>
                    <th>DNI</th>
                    <th>Apellido y nombre</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Huellas</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($docentes as $docente)
                <tr class="{{ $docente->activo ? '' : 'fila-inactiva' }}">
                    <td>{{ $docente->dni }}</td>
                    <td>{{ $docente->nombre_completo }}{{ $docente->activo ? '' : ' (inactivo)' }}</td>
                    <td>{{ $docente->email ?? '—' }}</td>
                    <td>{{ $docente->telefono ?? '—' }}</td>
                    <td>{{ $docente->huellas_count }}</td>
                    <td>
                        <div class="acciones-fila">
                            <a href="{{ route('docentes.edit', $docente) }}" class="btn-editar"><i class="fa-solid fa-pen"></i> Editar</a>

                            @if ($docente->activo)
                                <form method="POST" action="{{ route('docentes.desactivar', $docente) }}"
                                      onsubmit="return confirm('¿Desactivar a este docente? No podrá iniciar sesión ni registrar asistencia.');">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn-aviso">Desactivar</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('docentes.activar', $docente) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn-ok">Activar</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('docentes.destroy', $docente) }}"
                                  onsubmit="return confirm('¿Eliminar definitivamente a este docente? Solo es posible si no tiene asistencias registradas.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-peligro" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align: center; color: #64748b;">No se encontraron docentes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
