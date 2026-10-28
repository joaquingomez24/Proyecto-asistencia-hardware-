<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Asistencia EPET 20</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, Arial, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1rem; background: #f3f5f8; color: #151a23; }
        main { width: min(100%, 420px); background: #fff; border: 1px solid #dce1e8; border-radius: 14px; padding: 2rem; box-shadow: 0 12px 34px #16203312; }
        h1 { font-size: 1.45rem; margin: 0 0 .35rem; }
        p { color: #586273; line-height: 1.5; }
        label { display: block; margin: 1rem 0 .35rem; font-weight: 650; }
        input { width: 100%; padding: .75rem; border: 1px solid #bec7d4; border-radius: 8px; font: inherit; }
        button { width: 100%; margin-top: 1.35rem; padding: .8rem 1rem; background: #17243b; color: white; border: 0; border-radius: 8px; font: inherit; font-weight: 700; cursor: pointer; }
        .mensaje { padding: .8rem; border-radius: 8px; margin: .8rem 0; background: #e9f8ee; color: #14532d; }
        .error { padding: .8rem; border-radius: 8px; margin: .8rem 0; background: #fff0f0; color: #8b1c1c; }
        .ayuda { font-size: .9rem; margin-top: 1.2rem; }
    </style>
</head>
<body>
<main>
    <h1>Asistencia EPET 20</h1>
    <p>Ingresá con tu usuario, DNI y contraseña.</p>

    @if (session('exito'))
        <div class="mensaje" role="status">{{ session('exito') }}</div>
    @endif

    @if ($errors->any())
        <div class="error" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="usuario">Usuario</label>
        <input id="usuario" name="usuario" value="{{ old('usuario') }}" required autocomplete="username" maxlength="100" autofocus>

        <label for="dni">DNI</label>
        <input id="dni" name="dni" value="{{ old('dni') }}" required inputmode="numeric" autocomplete="off" maxlength="20">

        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">

        <button type="submit">Iniciar sesión</button>
    </form>
    <p class="ayuda">Si no podés ingresar, consultá a Secretaría.</p>
</main>
</body>
</html>
