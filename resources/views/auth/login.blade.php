
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Asistencia EPET 20</title>

    <style>
        :root {
            color-scheme: light;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            color: #172033;
            background: #edf2ff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at 15% 15%, #bfdbfe 0, transparent 35%),
                radial-gradient(circle at 85% 80%, #ddd6fe 0, transparent 35%),
                linear-gradient(135deg, #eef4ff, #e9eaff);
            overflow-x: hidden;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            border-radius: 50%;
            filter: blur(2px);
            pointer-events: none;
            z-index: 0;
        }

        body::before {
            width: 260px;
            height: 260px;
            background: #93c5fd55;
            top: -80px;
            right: -60px;
        }

        body::after {
            width: 320px;
            height: 320px;
            background: #c4b5fd55;
            bottom: -130px;
            left: -100px;
        }

        main {
            position: relative;
            z-index: 1;
            width: min(100%, 440px);
            padding: 38px;
            border: 1px solid rgba(255, 255, 255, .75);
            border-radius: 24px;
            background: rgba(255, 255, 255, .72);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow:
                0 24px 60px rgba(39, 54, 95, .13),
                inset 0 1px 0 rgba(255, 255, 255, .8);
        }

        .encabezado {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 66px;
            height: 66px;
            display: grid;
            place-items: center;
            margin: 0 auto 18px;
            border-radius: 20px;
            color: white;
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            box-shadow: 0 10px 25px rgba(79, 70, 229, .28);
        }

        h1 {
            margin: 0 0 9px;
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -.6px;
            color: #172554;
        }

        .subtitulo {
            margin: 0;
            color: #64748b;
            font-size: .95rem;
            line-height: 1.6;
        }

        .campo {
            margin-bottom: 19px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: .9rem;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #d8e0ef;
            border-radius: 12px;
            outline: none;
            background: rgba(255, 255, 255, .85);
            color: #172033;
            font: inherit;
            font-size: .95rem;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        input::placeholder {
            color: #a0aabd;
        }

        input:hover {
            border-color: #a5b4fc;
        }

        input:focus {
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .13);
        }

        button {
            width: 100%;
            margin-top: 10px;
            padding: 15px 20px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #6d28d9);
            color: white;
            font: inherit;
            font-weight: 750;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(79, 70, 229, .25);
            transition: transform .2s, box-shadow .2s, filter .2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(79, 70, 229, .32);
            filter: brightness(1.05);
        }

        button:active {
            transform: translateY(0);
        }

        button:focus-visible {
            outline: 3px solid #818cf8;
            outline-offset: 3px;
        }

        .mensaje,
        .error {
            padding: 13px 15px;
            border-radius: 12px;
            margin: 0 0 20px;
            font-size: .9rem;
            line-height: 1.5;
        }

        .mensaje {
            border: 1px solid #bbf7d0;
            background: rgba(220, 252, 231, .8);
            color: #166534;
        }

        .error {
            border: 1px solid #fecaca;
            background: rgba(254, 226, 226, .8);
            color: #991b1b;
        }

        .error div + div {
            margin-top: 5px;
        }

        .ayuda {
            margin: 24px 0 0;
            padding-top: 19px;
            border-top: 1px solid rgba(148, 163, 184, .25);
            color: #64748b;
            font-size: .85rem;
            line-height: 1.5;
            text-align: center;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            main {
                padding: 28px 22px;
                border-radius: 20px;
            }

            h1 {
                font-size: 1.45rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>
<main>
    <div class="encabezado">
        <div class="logo" aria-hidden="true">EP</div>
        <h1>Asistencia EPET 20</h1>
        <p class="subtitulo">
            Ingresá con tu usuario, DNI y contraseña.
        </p>
    </div>

    @if (session('exito'))
        <div class="mensaje" role="status">
            {{ session('exito') }}
        </div>
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

        <div class="campo">
            <label for="usuario">Usuario</label>
            <input
                id="usuario"
                name="usuario"
                type="text"
                value="{{ old('usuario') }}"
                placeholder="Ingresá tu usuario"
                required
                autocomplete="username"
                maxlength="100"
                autofocus
            >
        </div>

        <div class="campo">
            <label for="dni">DNI</label>
            <input
                id="dni"
                name="dni"
                type="text"
                value="{{ old('dni') }}"
                placeholder="Ingresá tu DNI"
                required
                inputmode="numeric"
                autocomplete="off"
                maxlength="20"
            >
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input
                id="password"
                name="password"
                type="password"
                placeholder="Ingresá tu contraseña"
                required
                autocomplete="current-password"
            >
        </div>

        <button type="submit">
            Iniciar sesión
        </button>
    </form>

    <p class="ayuda">
        Si no podés ingresar, consultá a Secretaría.
    </p>
</main>
</body>
</html>
