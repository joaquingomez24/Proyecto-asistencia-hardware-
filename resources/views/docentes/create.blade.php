@extends('layouts.app')

@section('title', 'Registrar Docente')

@section('content')

<div class="contenedor">
    <div class="formulario">
        <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
            <div style="background-color: #e8f0fe; color: #0d47a1; width: 60px; height: 60px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0; margin-right: 1rem;">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <div>
                <h2 style="color: #1138a6; font-weight: 800; font-family: 'Segoe UI', sans-serif; margin: 0; font-size: 1.8rem;">Consulta de Asistencias</h2>
                <p style="color: #6c757d; margin: 0; font-size: 0.95rem;">Visualice el listado de asistencias registradas.</p>
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

        <form action="{{ route('docentes.store') }}" method="POST">
            @csrf
            <div class="fila">
                <div class="campo">
                    <label>Nombre</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" required>
                </div>
                <div class="campo">
                    <label>Apellido</label>
                    <input type="text" name="apellido" value="{{ old('apellido') }}" required>
                </div>
            </div>

            <div class="fila">
                <div class="campo">
                    <label>DNI</label>
                    <input type="text" name="DNI" value="{{ old('DNI') }}" required>
                </div>
                <div class="campo">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="{{ old('telefono') }}" required>
                </div>
            </div>

            <div class="fila">
                <div class="campo">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="campo">
                    <label>Código de huella</label>
                    <input type="number" name="id_huella" value="{{ old('id_huella') }}" required>
                </div>
            </div>

            <h2>Horarios</h2>
            <div id="contenedor-horarios">
                @if(old('materia'))
                    @foreach(old('materia') as $i => $mat)
                        <div class="bloque-horario">
                            @if($i > 0)
                                <div style="display: flex; justify-content: flex-end; margin-top: 15px; margin-bottom: 10px;">
                                    <button type="button" onclick="this.closest('.bloque-horario').remove()" style="background-color: #dc3545; color: #fff; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer;">
                                        <i class="fa-solid fa-trash"></i> Eliminar horario
                                    </button>
                                </div>
                            @endif
                            <div class="fila">
                                <div class="campo">
                                    <label>Materia</label>
                                    <input type="text" name="materia[]" value="{{ old('materia.'.$i) }}" required>
                                </div>
                                <div class="campo">
                                    <label>Turno</label>
                                    <select name="turno[]" required>
                                        <option value="Mañana" {{ old('turno.'.$i) == 'Mañana' ? 'selected' : '' }}>Mañana</option>
                                        <option value="Tarde" {{ old('turno.'.$i) == 'Tarde' ? 'selected' : '' }}>Tarde</option>
                                        <option value="Vespertino" {{ old('turno.'.$i) == 'Vespertino' ? 'selected' : '' }}>Vespertino</option>
                                    </select>
                                </div>
                                <div class="campo">
                                    <label>Curso</label>
                                    <select name="curso[]" required>
                                        @foreach(['1°', '2°', '3°', '4°', '5°', '6°'] as $c)
                                            <option value="{{ $c }}" {{ old('curso.'.$i) == $c ? 'selected' : '' }}>{{ $c }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="campo">
                                    <label>División</label>
                                    <select name="division[]" required>
                                        @foreach(['1°', '2°', '3°', '4°', '5°', '6°'] as $d)
                                            <option value="{{ $d }}" {{ old('division.'.$i) == $d ? 'selected' : '' }}>{{ $d }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="fila">
                                <div class="campo">
                                    <label>Entrada</label>
                                    <input type="time" name="entrada[]" value="{{ old('entrada.'.$i) }}" required>
                                </div>
                                <div class="campo">
                                    <label>Salida</label>
                                    <input type="time" name="salida[]" value="{{ old('salida.'.$i) }}" required>
                                </div>
                                <div class="campo">
                                    <label for="dia">Día</label>
                                    <select name="dia[]" required>
                                        <option value="">Seleccione día</option>
                                        @foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'] as $dia)
                                            <option value="{{ $dia }}" {{ old('dia.'.$i) == $dia ? 'selected' : '' }}>{{ $dia }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="bloque-horario">
                        <div class="fila">
                            <div class="campo">
                                <label>Materia</label>
                                <input type="text" name="materia[]" required>
                            </div>
                            <div class="campo">
                                <label>Turno</label>
                                <select name="turno[]" required>
                                    <option value="Mañana">Mañana</option>
                                    <option value="Tarde">Tarde</option>
                                    <option value="Vespertino">Vespertino</option>
                                </select>
                            </div>
                            <div class="campo">
                                <label>Curso</label>
                                <select name="curso[]" required>
                                    <option>1°</option><option>2°</option><option>3°</option>
                                    <option>4°</option><option>5°</option><option>6°</option>
                                </select>
                            </div>
                            <div class="campo">
                                <label>División</label>
                                <select name="division[]" required>
                                    <option>1°</option><option>2°</option><option>3°</option>
                                    <option>4°</option><option>5°</option><option>6°</option>
                                </select>
                            </div>
                        </div>

                        <div class="fila">
                            <div class="campo">
                                <label>Entrada</label>
                                <input type="time" name="entrada[]" required>
                            </div>
                            <div class="campo">
                                <label>Salida</label>
                                <input type="time" name="salida[]" required>
                            </div>
                            <div class="campo">
                                <label for="dia">Día</label>
                                <select name="dia[]" required>
                                    <option value="">Seleccione día</option>
                                    <option value="Lunes">Lunes</option>
                                    <option value="Martes">Martes</option>
                                    <option value="Miércoles">Miércoles</option>
                                    <option value="Jueves">Jueves</option>
                                    <option value="Viernes">Viernes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <br>
            <button type="button" id="btn-agregar">+ Agregar horario</button>
            <br><br>
            <button type="submit">Guardar docente</button>
        </form>
    </div>
</div>

<script>
    const alerta = document.querySelector('.alerta');
    if (alerta) {
        setTimeout(() => {
            alerta.style.transition = 'opacity 0.5s ease';
            alerta.style.opacity = '0';
            setTimeout(() => alerta.remove(), 500);
        }, 4000);
    }

    document.getElementById('btn-agregar').addEventListener('click', function() {
        var contenedor = document.getElementById('contenedor-horarios');
        var primerBloque = document.querySelector('.bloque-horario');
        var nuevoBloque = primerBloque.cloneNode(true);
        
        // Limpiamos los valores clonados
        nuevoBloque.querySelectorAll('input').forEach(input => input.value = '');
        nuevoBloque.querySelectorAll('select').forEach(select => select.selectedIndex = 0);

        // Si el bloque clonado tenía la barra del botón eliminar previa, se la quitamos para rehacerla limpia
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