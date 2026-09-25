@extends('layouts.app')

@section('title', 'Editar Docente')

@section('content')

<div class="contenedor">
    <div class="formulario">
        <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
            <div style="background-color: #e8f0fe; color: #0d47a1; width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0; margin-right: 1rem;">
                <i class="fa-solid fa-user-pen"></i>
            </div>
            <div>
                <h2 style="color: #1138a6; font-weight: 800; font-family: 'Segoe UI', sans-serif; margin: 0; font-size: 1.8rem;">Editar Docente</h2>
                <p style="color: #6c757d; margin: 0; font-size: 0.95rem;">Modifique los datos y horarios del docente seleccionado.</p>
            </div>
        </div>

        @if(session('exito'))
            <div class="alerta exito"><i class="fa-solid fa-circle-check"></i> {{ session('exito') }}</div>
        @endif

        @if ($errors->any())
            <div class="alerta error" style="background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px; font-weight: bold; margin-bottom: 5px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>No se pudo guardar la información:</span>
                </div>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('docentes.update', $docente->id_docente) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="fila">
                <div class="campo">
                    <label>Nombre</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $docente->nombre) }}" required>
                </div>
                <div class="campo">
                    <label>Apellido</label>
                    <input type="text" name="apellido" value="{{ old('apellido', $docente->apellido) }}" required>
                </div>
            </div>

            <div class="fila">
                <div class="campo">
                    <label>DNI</label>
                    <input type="text" name="DNI" value="{{ old('DNI', $docente->DNI) }}" required>
                </div>
                <div class="campo">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="{{ old('telefono', $docente->telefono) }}" required>
                </div>
            </div>

            <div class="fila">
                <div class="campo">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $docente->email) }}" required>
                </div>
                <div class="campo">
                    <label>Código de huella</label>
                    <input type="number" name="id_huella" value="{{ old('id_huella', $docente->id_huella) }}" required>
                </div>
            </div>

            <h2>Horarios</h2>
            <div id="contenedor-horarios">
                @php
                    $materiasCargadas = old('materia', $docente->materias);
                @endphp

                @foreach($materiasCargadas as $i => $materia)
                    @php
                        $isOld = is_array(old('materia'));
                        $idMat = $isOld ? (old('id_materia.'.$i) ?? '') : $materia->id_materias;
                        $nomMat = $isOld ? $materia : $materia->nombre;
                        $turnoMat = $isOld ? old('turno.'.$i) : $materia->turno;
                        $cursoMat = $isOld ? old('curso.'.$i) : $materia->curso;
                        $divMat = $isOld ? old('division.'.$i) : $materia->division;
                        $entMat = $isOld ? old('entrada.'.$i) : $materia->horario_inicio;
                        $salMat = $isOld ? old('salida.'.$i) : $materia->horario_finalizacion;
                        $diaMat = $isOld ? old('dia.'.$i) : $materia->dia;
                    @endphp

                    <div class="bloque-horario">
                        <input type="hidden" name="id_materia[]" value="{{ $idMat }}">
                        
                        @if($i > 0)
                            <div class="header-eliminar" style="display: flex; justify-content: flex-end; margin-top: 15px; margin-bottom: 10px;">
                                <button type="button" onclick="this.closest('.bloque-horario').remove()" style="background-color: #dc3545; color: #fff; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer;">
                                    <i class="fa-solid fa-trash"></i> Eliminar horario
                                </button>
                            </div>
                        @endif

                        <div class="fila">
                            <div class="campo">
                                <label>Materia</label>
                                <input type="text" name="materia[]" value="{{ $nomMat }}" required>
                            </div>
                            <div class="campo">
                                <label>Turno</label>
                                <select name="turno[]" required>
                                    <option value="Mañana" {{ $turnoMat == 'Mañana' ? 'selected' : '' }}>Mañana</option>
                                    <option value="Tarde" {{ $turnoMat == 'Tarde' ? 'selected' : '' }}>Tarde</option>
                                    <option value="Vespertino" {{ $turnoMat == 'Vespertino' ? 'selected' : '' }}>Vespertino</option>
                                </select>
                            </div>
                            <div class="campo">
                                <label>Curso</label>
                                <select name="curso[]" required>
                                    @foreach(['1°', '2°', '3°', '4°', '5°', '6°'] as $c)
                                        <option value="{{ $c }}" {{ $cursoMat == $c ? 'selected' : '' }}>{{ $c }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="campo">
                                <label>División</label>
                                <select name="division[]" required>
                                    @foreach(['1°', '2°', '3°', '4°', '5°', '6°'] as $d)
                                        <option value="{{ $d }}" {{ $divMat == $d ? 'selected' : '' }}>{{ $d }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="fila">
                            <div class="campo">
                                <label>Entrada</label>
                                <input type="time" name="entrada[]" value="{{ $entMat }}" required>
                            </div>
                            <div class="campo">
                                <label>Salida</label>
                                <input type="time" name="salida[]" value="{{ $salMat }}" required>
                            </div>
                            <div class="campo">
                                <label for="dia">Día</label>
                                <select name="dia[]" required>
                                    <option value="">Seleccione día</option>
                                    @foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'] as $dia)
                                        <option value="{{ $dia }}" {{ $diaMat == $dia ? 'selected' : '' }}>{{ $dia }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <br>
            <button type="button" id="btn-agregar">+ Agregar horario</button>
            <br><br>
            <button type="submit">Actualizar docente</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('btn-agregar').addEventListener('click', function() {
        var contenedor = document.getElementById('contenedor-horarios');
        var primerBloque = document.querySelector('.bloque-horario');
        var nuevoBloque = primerBloque.cloneNode(true);
        
        // Limpiar inputs y desvincular el ID de materia previa para que sea una nueva inserción
        nuevoBloque.querySelectorAll('input').forEach(input => {
            if(input.type === 'hidden') {
                input.value = '';
            } else {
                input.value = '';
            }
        });
        nuevoBloque.querySelectorAll('select').forEach(select => select.selectedIndex = 0);

        var headerExistente = nuevoBloque.querySelector('.header-eliminar');
        if(headerExistente) headerExistente.remove();

        var divHeader = document.createElement('div');
        divHeader.className = 'header-eliminar';
        divHeader.style.display = 'flex';
        divHeader.style.justifyContent = 'flex-end';
        divHeader.style.marginTop = '15px';
        divHeader.style.marginBottom = '10px';

        var btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.innerHTML = '<i class="fa-solid fa-trash"></i> Eliminar horario';
        btnEliminar.style.backgroundColor = '#dc3545';
        btnEliminar.style.color = '#fff';
        btnEliminar.style.border = 'none';
        btnEliminar.style.padding = '6px 12px';
        btnEliminar.style.borderRadius = '5px';
        btnEliminar.style.cursor = 'pointer';

        btnEliminar.addEventListener('click', function() {
            nuevoBloque.remove();
        });

        divHeader.appendChild(btnEliminar);
        nuevoBloque.insertBefore(divHeader, nuevoBloque.firstChild);

        contenedor.appendChild(nuevoBloque);
    });
</script>
@endsection