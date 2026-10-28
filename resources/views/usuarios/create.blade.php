<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrar cuentas | Asistencia EPET 20</title>
    <style>
        :root { font-family: system-ui, Arial, sans-serif; color: #171d28; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f5f8; padding: 1.5rem; }
        main { width: min(1000px, 100%); margin: auto; }
        section { background: #fff; border: 1px solid #dce1e8; border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem; }
        h1 { font-size: 1.35rem; margin-top: 0; }
        h2 { font-size: 1.1rem; }
        label { display: block; margin: .8rem 0 .3rem; font-weight: 650; }
        input, select { width: 100%; padding: .7rem; font: inherit; border: 1px solid #bec7d4; border-radius: 8px; }
        button { margin-top: 1rem; padding: .75rem 1rem; border: 0; border-radius: 8px; background: #17243b; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .success, .error { padding: .75rem; border-radius: 8px; margin: .8rem 0; }
        .success { background: #e9f8ee; color: #14532d; } .error { background: #fff0f0; color: #8b1c1c; }
        table { width: 100%; border-collapse: collapse; } th, td { text-align: left; padding: .6rem .4rem; border-bottom: 1px solid #e2e7ee; }
    </style>
</head>
<body>
<main>
    <section>
        <h1>Administrar cuentas</h1>
        <p>Solo Secretaría puede crear cuentas. Para un docente, primero debe existir su ficha en el sistema.</p>
        @if (session('exito')) <div class="success" role="status">{{ session('exito') }}</div> @endif
        @if ($errors->any())
            <div class="error" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        @endif
        <form method="POST" action="{{ route('usuarios.store') }}">
            @csrf
            <label for="usuario">Usuario</label>
            <input id="usuario" name="usuario" value="{{ old('usuario') }}" required maxlength="100">
            <label for="dni">DNI</label>
            <input id="dni" name="dni" value="{{ old('dni') }}" required inputmode="numeric" maxlength="10">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255">
            <label for="rol">Rol</label>
            <select id="rol" name="rol" required>
                <option value="">Elegí un rol</option>
                <option value="docente" @selected(old('rol') === 'docente')>Docente</option>
                <option value="prosecretario" @selected(old('rol') === 'prosecretario')>Prosecretario</option>
                <option value="jefe_preceptores" @selected(old('rol') === 'jefe_preceptores')>Jefe de preceptores</option>
            </select>
            <label for="id_docente">Ficha de docente (solo para el rol Docente)</label>
            <select id="id_docente" name="id_docente">
                <option value="">No corresponde / seleccionar después</option>
                @foreach ($docentesSinCuenta as $docente)
                    <option value="{{ $docente->id_docente }}" @selected((string) old('id_docente') === (string) $docente->id_docente)>
                        {{ $docente->apellido }}, {{ $docente->nombre }} — DNI {{ $docente->DNI }}
                    </option>
                @endforeach
            </select>
            <label for="password">Contraseña (mínimo 8 caracteres)</label>
            <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
            <label for="password_confirmation">Repetir contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
            <button type="submit">Crear cuenta</button>
        </form>
    </section>
    <section>
        <h2>Cuentas existentes</h2>
        <table>
            <thead><tr><th>Usuario</th><th>DNI</th><th>Rol</th><th>Docente vinculado</th></tr></thead>
            <tbody>
            @forelse ($usuarios as $usuario)
                <tr><td>{{ $usuario->usuario }}</td><td>{{ $usuario->dni }}</td><td>{{ $usuario->rol }}</td><td>{{ $usuario->id_docente ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="4">Todavía no hay cuentas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</main>
<script>
(() => {
    const rol = document.getElementById('rol');
    const docente = document.getElementById('id_docente');
    function actualizar() {
        const esDocente = rol.value === 'docente';
        docente.required = esDocente;
        docente.disabled = !esDocente;
    }
    rol.addEventListener('change', actualizar);
    actualizar();
})();
</script>
</body>
</html>
