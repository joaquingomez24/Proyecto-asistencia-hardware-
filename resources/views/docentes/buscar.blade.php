@extends('layouts.app')

@section('title', 'Buscar Docente para Editar')

@section('content')
<div class="card-consulta card-editar">
    <div class="header-consulta">
        <div class="icono-box">
            <i class="fa-solid fa-user-pen"></i>
        </div>
        <div>
            <h2>Gestión de Docentes</h2>
            <p>Busque un docente por nombre, apellido o DNI para modificar sus datos u horarios.</p>
        </div>
    </div>

    @if(session('exito'))
        <div class="alerta exito" style="margin-bottom: 20px;">
            <i class="fa-solid fa-circle-check"></i> {{ session('exito') }}
        </div>
    @endif

    <form action="{{ route('docentes.buscar') }}" method="GET" class="form-busqueda" style="margin-bottom: 25px;">
        <div class="fila" style="align-items: flex-end;">
            <div class="campo" style="flex: 1;">
                <label for="buscar">Buscar docente</label>
                <input type="text" name="buscar" id="buscar" placeholder="Ingrese nombre, apellido o DNI..." value="{{ request('buscar') }}">
            </div>
            <div class="campo" style="flex: 0 0 auto;">
                <button type="submit" class="btn-buscar">
                    <i class="fa-solid fa-magnifying-glass"></i> Buscar
                </button>
            </div>
        </div>
    </form>

    <div class="tabla-contenedor">
        <table class="tabla-asistencias" style="width: 100%;">
            <thead>
                <tr>
                    <th>DNI</th>
                    <th>Apellido y Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th style="text-align: center;">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($docentes as $docente)
                    @php
                        $idDocente = $docente->id_docente ?? $docente->id;
                        $esInactivo = isset($docente->activo) && !$docente->activo;
                        $nombreCompleto = $docente->apellido . ', ' . $docente->nombre;
                    @endphp
                    <tr class="fila-docente-{{ $idDocente }}" style="{{ $esInactivo ? 'background-color: #f8fafc; color: #94a3b8;' : '' }}">
                        <td class="col-dni">{{ $docente->DNI }}</td>
                        <td class="col-nombre">{{ $docente->apellido }}, {{ $docente->nombre }}</td>
                        <td class="col-email">{{ $docente->email }}</td>
                        <td class="col-telefono">{{ $docente->telefono }}</td>
                        <td>
                            <div style="display: flex; gap: 8px; align-items: center; justify-content: center;">
                                
                                {{-- Botón Editar --}}
                                <a href="{{ route('docentes.edit', $idDocente) }}" 
                                   class="btn-editar btn-editar-{{ $idDocente }}" 
                                   style="{{ $esInactivo ? 'pointer-events: none; opacity: 0.4; background-color: #cbd5e1; color: #64748b;' : '' }}">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </a>

                                {{-- Botón Estado (Desactivar / Activar) --}}
                                <div class="contenedor-btn-estado-{{ $idDocente }}">
                                    @if(!$esInactivo)
                                        <button type="button" 
                                                class="btn-estado-docente"
                                                onclick="solicitarCambioEstado({{ $idDocente }}, 'desactivar', '{{ $nombreCompleto }}')" 
                                                onmouseover="this.style.borderColor='#e6a100';" 
                                                onmouseout="this.style.borderColor='#e6a100';"
                                                style="background-color: #ffffff; color: #e6a100; border: 1.5px solid #e6a100; padding: 6px 12px; border-radius: 8px; font-weight: bold; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 5px;">
                                            Desactivar docente
                                        </button>
                                    @else
                                        <button type="button" 
                                                class="btn-estado-docente"
                                                onclick="solicitarCambioEstado({{ $idDocente }}, 'activar', '{{ $nombreCompleto }}')" 
                                                onmouseover="this.style.borderColor='#16a34a'; this.style.backgroundColor='#f0fdf4';" 
                                                onmouseout="this.style.borderColor='#16a34a'; this.style.backgroundColor='#ffffff';"
                                                style="background-color: #ffffff; color: #16a34a; border: 1.5px solid #16a34a; padding: 6px 12px; border-radius: 8px; font-weight: bold; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 5px;">
                                            Activar docente
                                        </button>
                                    @endif
                                </div>

                                {{-- Botón Borrar --}}
                                <button type="button" 
                                        class="btn-borrar-{{ $idDocente }}"
                                        onclick="solicitarEliminarDocente({{ $idDocente }}, '{{ $nombreCompleto }}')" 
                                        title="Eliminar docente"
                                        onmouseover="if(!this.disabled) this.style.backgroundColor='#bb2d3b';"
                                        onmouseout="if(!this.disabled) this.style.backgroundColor='#dc3545';"
                                        {{ $esInactivo ? 'disabled' : '' }}
                                        style="background-color: {{ $esInactivo ? '#cbd5e1' : '#dc3545' }}; color: {{ $esInactivo ? '#64748b' : 'white' }}; border: none; padding: 8px 10px; border-radius: 8px; cursor: {{ $esInactivo ? 'not-allowed' : 'pointer' }}; display: inline-flex; align-items: center; justify-content: center; opacity: {{ $esInactivo ? '0.5' : '1' }}; transition: background-color 0.2s ease;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>

                                <div id="modal-eliminar-docente" class="modal-logout-overlay" style="display: none;">
                                    <div class="modal-logout-box">
                                        <div class="modal-logout-icon">
                                            <i class="fa-solid fa-trash-can" style="color: #dc3545;"></i>
                                        </div>
                                        <h3 class="modal-logout-title">Eliminar docente</h3>
                                        <p class="modal-logout-text" id="modal-eliminar-mensaje">¿Está seguro de que desea eliminar a este docente de forma permanente?</p>
                                        <div class="modal-logout-buttons">
                                            <button type="button" class="btn-modal-cancelar" onclick="cerrarModalEliminar()">Cancelar</button>
                                            <button type="button" class="btn-modal-aceptar" style="background-color: #dc3545;" onclick="ejecutarEliminacionDocente()">Aceptar</button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">
                            No se encontraron docentes registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Confirmación para Activar / Desactivar -->
<div id="modal-estado-docente" class="modal-logout-overlay" style="display: none;">
    <div class="modal-logout-box">
        <div class="modal-logout-icon" id="modal-estado-icono">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <h3 class="modal-logout-title" id="modal-estado-titulo">Confirmar acción</h3>
        <p class="modal-logout-text" id="modal-estado-mensaje">¿Está seguro de realizar esta acción?</p>
        <div class="modal-logout-buttons">
            <button type="button" class="btn-modal-cancelar" onclick="cerrarModalEstado()">Cancelar</button>
            <button type="button" class="btn-modal-aceptar" id="btn-modal-confirmar-accion" onclick="ejecutarCambioEstado()">Aceptar</button>
        </div>
    </div>
</div>

<script>
    let docenteIdSeleccionado = null;
    let accionSeleccionada = null;

    function solicitarCambioEstado(id, accion, nombreDocente) {
        docenteIdSeleccionado = id;
        accionSeleccionada = accion;

        const modal = document.getElementById('modal-estado-docente');
        const titulo = document.getElementById('modal-estado-titulo');
        const mensaje = document.getElementById('modal-estado-mensaje');
        const icono = document.getElementById('modal-estado-icono');
        const btnConfirmar = document.getElementById('btn-modal-confirmar-accion');

        if (accion === 'desactivar') {
            titulo.innerText = 'Desactivar docente';
            mensaje.innerText = `¿Está seguro de que desea desactivar a ${nombreDocente}?`;
            icono.innerHTML = '<i class="fa-solid fa-user-slash" style="color: #e6a100;"></i>';
            btnConfirmar.style.backgroundColor = '#e6a100';
        } else {
            titulo.innerText = 'Activar docente';
            mensaje.innerText = `¿Está seguro de que desea activar a ${nombreDocente}?`;
            icono.innerHTML = '<i class="fa-solid fa-user-check" style="color: #16a34a;"></i>';
            btnConfirmar.style.backgroundColor = '#16a34a';
        }

        modal.style.display = 'flex';
    }

    function cerrarModalEstado() {
        document.getElementById('modal-estado-docente').style.display = 'none';
        docenteIdSeleccionado = null;
        accionSeleccionada = null;
    }

    function ejecutarCambioEstado() {
        if (!docenteIdSeleccionado || !accionSeleccionada) return;

        const id = docenteIdSeleccionado;
        const endpoint = accionSeleccionada === 'desactivar' ? `/docentes/${id}/desactivar` : `/docentes/${id}/activar`;

        fetch(endpoint, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                const filas = document.querySelectorAll(`.fila-docente-${id}`);
                
                filas.forEach(fila => {
                    fila.style.transition = 'all 0.3s ease';
                    
                    const btnEditar = fila.querySelector(`.btn-editar-${id}`);
                    const contenedorBtnEstado = fila.querySelector(`.contenedor-btn-estado-${id}`);
                    const btnBorrar = fila.querySelector(`.btn-borrar-${id}`);

                    if (accionSeleccionada === 'desactivar') {
                        // Cambiar la fila a tono gris
                        fila.style.backgroundColor = '#f8fafc';
                        fila.style.color = '#94a3b8';

                        // Deshabilitar Botón Editar
                        if (btnEditar) {
                            btnEditar.style.pointerEvents = 'none';
                            btnEditar.style.opacity = '0.4';
                            btnEditar.style.backgroundColor = '#cbd5e1';
                            btnEditar.style.color = '#64748b';
                        }

                        // Deshabilitar Botón Borrar
                        if (btnBorrar) {
                            btnBorrar.disabled = true;
                            btnBorrar.style.backgroundColor = '#cbd5e1';
                            btnBorrar.style.color = '#64748b';
                            btnBorrar.style.cursor = 'not-allowed';
                            btnBorrar.style.opacity = '0.5';
                        }

                        // Cambiar a botón Activar Docente (Verde)
                        const nombreDocente = fila.querySelector('.col-nombre') ? fila.querySelector('.col-nombre').innerText : 'este docente';
                        contenedorBtnEstado.innerHTML = `
                            <button type="button" 
                                    class="btn-estado-docente"
                                    onclick="solicitarCambioEstado(${id}, 'activar', '${nombreDocente}')" 
                                    onmouseover="this.style.borderColor='#16a34a'; this.style.backgroundColor='#f0fdf4';" 
                                    onmouseout="this.style.borderColor='#16a34a'; this.style.backgroundColor='#ffffff';"
                                    style="background-color: #ffffff; color: #16a34a; border: 1.5px solid #16a34a; padding: 6px 12px; border-radius: 8px; font-weight: bold; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 5px;">
                                Activar docente
                            </button>
                        `;
                    } else {
                        // Restaurar tono normal de la fila
                        fila.style.backgroundColor = '';
                        fila.style.color = '';

                        // Habilitar Botón Editar
                        if (btnEditar) {
                            btnEditar.style.pointerEvents = 'auto';
                            btnEditar.style.opacity = '1';
                            btnEditar.style.backgroundColor = '';
                            btnEditar.style.color = '';
                        }

                        // Habilitar Botón Borrar
                        if (btnBorrar) {
                            btnBorrar.disabled = false;
                            btnBorrar.style.backgroundColor = '#dc3545';
                            btnBorrar.style.color = 'white';
                            btnBorrar.style.cursor = 'pointer';
                            btnBorrar.style.opacity = '1';
                        }

                        // Cambiar a botón Desactivar Docente (Amarillo)
                        const nombreDocente = fila.querySelector('.col-nombre') ? fila.querySelector('.col-nombre').innerText : 'este docente';
                        contenedorBtnEstado.innerHTML = `
                            <button type="button" 
                                    class="btn-estado-docente"
                                    onclick="solicitarCambioEstado(${id}, 'desactivar', '${nombreDocente}')" 
                                    onmouseover="this.style.borderColor='#e6a100';" 
                                    onmouseout="this.style.borderColor='#e6a100';"
                                    style="background-color: #ffffff; color: #e6a100; border: 1.5px solid #e6a100; padding: 6px 12px; border-radius: 8px; font-weight: bold; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 5px;">
                                Desactivar docente
                            </button>
                        `;
                    }
                });

                cerrarModalEstado();
            } else {
                alert('No se pudo cambiar el estado del docente.');
                cerrarModalEstado();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            cerrarModalEstado();
        });
    }

    function solicitarEliminarDocente(id, nombreDocente) {
        docenteIdAEliminar = id;
        const modal = document.getElementById('modal-eliminar-docente');
        const mensaje = document.getElementById('modal-eliminar-mensaje');

        mensaje.innerText = `¿Está seguro de que desea eliminar a ${nombreDocente} de forma permanente?`;
        modal.style.display = 'flex';
    }

    function cerrarModalEliminar() {
        document.getElementById('modal-eliminar-docente').style.display = 'none';
        docenteIdAEliminar = null;
    }

    function ejecutarEliminacionDocente() {
        if (!docenteIdAEliminar) return;

        const id = docenteIdAEliminar;

        fetch(`/docentes/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                const filas = document.querySelectorAll(`.fila-docente-${id}`);
                filas.forEach(fila => {
                    fila.style.transition = 'all 0.4s ease';
                    fila.style.opacity = '0';
                    setTimeout(() => fila.remove(), 400);
                });
                cerrarModalEliminar();
            } else {
                alert('No se pudo eliminar el docente.');
                cerrarModalEliminar();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            cerrarModalEliminar();
        });
    }
</script>
@endsection