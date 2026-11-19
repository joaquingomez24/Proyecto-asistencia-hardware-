@extends('layouts.app')

@section('title', 'Cuentas')

@section('content')
<div class="card-consulta">
    <div class="header-consulta">
        <div class="icono-box"><i class="fa-solid fa-user-shield"></i></div>
        <div>
            <h2>Cuentas de acceso</h2>
            <p>Creá cuentas para secretarios, jefes de preceptores y docentes. Para un docente, primero debe existir su ficha.</p>
        </div>
    </div>

    <x-errores />

    <form method="POST" action="{{ route('usuarios.store') }}">
        @csrf
        <div class="fila">
            <div class="campo">
                <label for="rol">Rol</label>
                <select id="rol" name="rol" required>
                    <option value="">Elegí un rol</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->value }}" @selected(old('rol') === $rol->value)>{{ $rol->etiqueta() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="campo" id="campo-docente">
                <label for="docente_id">Ficha del docente</label>
                <select id="docente_id" name="docente_id">
                    <option value="">Elegí un docente sin cuenta…</option>
                    @foreach ($docentesSinCuenta as $docente)
                        <option value="{{ $docente->id }}" @selected((string) old('docente_id') === (string) $docente->id)>
                            {{ $docente->nombre_completo }} — DNI {{ $docente->dni }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="campo" id="campo-dni">
                <label for="dni">DNI</label>
                <input id="dni" name="dni" type="text" inputmode="numeric" maxlength="10" value="{{ old('dni') }}">
            </div>
        </div>

        <div class="fila">
            <div class="campo">
                <label for="usuario">Usuario</label>
                <input id="usuario" name="usuario" type="text" maxlength="100" required value="{{ old('usuario') }}">
            </div>
            <div class="campo">
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email" maxlength="255" required value="{{ old('email') }}">
            </div>
        </div>

        <div class="fila">
            <div class="campo">
                <label for="password">Contraseña (mínimo 8 caracteres)</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
            </div>
            <div class="campo">
                <label for="password_confirmation">Repetir contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
            </div>
        </div>

        <div class="acciones"><button type="submit">Crear cuenta</button></div>
    </form>
</div>

<div class="card-consulta">
    <div class="header-consulta">
        <div class="icono-box"><i class="fa-solid fa-users"></i></div>
        <div><h2>Cuentas existentes</h2></div>
    </div>

    <div class="tabla-scroll">
        <table>
            <thead><tr><th>Usuario</th><th>DNI</th><th>Rol</th><th>Docente vinculado</th></tr></thead>
            <tbody>
            @forelse ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->usuario }}</td>
                    <td>{{ $usuario->dni }}</td>
                    <td>{{ $usuario->rol->etiqueta() }}</td>
                    <td>{{ $usuario->docente?->nombre_completo ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align: center; color: #64748b;">Todavía no hay cuentas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var rol = document.getElementById('rol');
        var campoDocente = document.getElementById('campo-docente');
        var campoDni = document.getElementById('campo-dni');
        var docente = document.getElementById('docente_id');
        var dni = document.getElementById('dni');

        function actualizar() {
            var esDocente = rol.value === 'docente';
            var requiereDni = rol.value !== '' && !esDocente;
            campoDocente.style.display = esDocente ? '' : 'none';
            campoDni.style.display = requiereDni ? '' : 'none';
            docente.required = esDocente;
            dni.required = requiereDni;
        }

        rol.addEventListener('change', actualizar);
        actualizar();
    })();
</script>
@endpush
