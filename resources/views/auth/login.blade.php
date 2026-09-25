<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio de sesión</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-page">

<div class="container">

    <img src="{{ asset('images/logo_epet.png') }}" alt="Logo EPET N°20" class="logo">

    <div class="heading">Sistema de Control de Asistencia</div>

    @if ($errors->any())
    <p style="color: red; text-align: center;">{{ $errors->first() }}</p>
@endif

   <form class="form" action="{{ route('login') }}" method="POST">
    @csrf

    <input placeholder="Usuario" id="usuario" name="name" type="text" class="input" value="{{ old('name') }}" required autofocus>
    <input placeholder="Contraseña" id="contraseña" name="password" type="password" class="input" required>

    <input value="Iniciar Sesión" type="submit" class="login-button"/>
</form>
</div>

</body>
</html>