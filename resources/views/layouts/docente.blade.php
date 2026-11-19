<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mi asistencia') | EPET 20</title>
    <link rel="stylesheet" href="{{ asset('css/docente.css') }}">
</head>
<body>
<header class="cabecera">
    <div class="marca">
        <img src="{{ asset('images/logo_epet.png') }}" alt="EPET N° 20">
        <span>Asistencia docente</span>
    </div>
    @auth
        <form id="form-logout" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-salir">Cerrar sesión</button>
        </form>
    @endauth
</header>

<main class="contenido">
    @yield('content')
</main>

@stack('scripts')
</body>
</html>
