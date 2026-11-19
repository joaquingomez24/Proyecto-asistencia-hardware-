<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Asistencia') | EPET 20</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @stack('estilos')
</head>
<body>
    <x-navbar />

    @if (session('exito'))
        <div class="alerta exito alerta-flash" role="status">
            <i class="fa-solid fa-circle-check"></i> {{ session('exito') }}
        </div>
    @endif

    @yield('content')

    <script>
        // Los mensajes de éxito se desvanecen solos.
        document.querySelectorAll('.alerta-flash').forEach(function (el) {
            setTimeout(function () {
                el.style.transition = 'opacity .5s ease';
                el.style.opacity = '0';
                setTimeout(function () { el.remove(); }, 500);
            }, 4000);
        });
    </script>
    @stack('scripts')
</body>
</html>
