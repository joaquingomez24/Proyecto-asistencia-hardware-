@php
    $route = Route::currentRouteName();
@endphp

<div class="navbar">
    <div class="nav-brand">
        <img src="{{ asset('images/logo_epet.png') }}" alt="Epet N 20" class="nav-logo">
        <div class="nav-title">
            <h2>Epet N° 20</h2>
            <span>Sistema de asistencia</span>
        </div>
    </div>

    <div class="nav-links">
        <a href="{{ route('home') }}" class="{{ $route == 'home' ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i> Inicio
        </a>
        @if(Auth::check() && Auth::user()->rol === 'prosecretario')
        <a href="{{ route('docentes.create') }}" class="nav-item {{ request()->routeIs('docentes.create') ? 'active' : '' }}">
            <i class="fa-solid fa-user-plus"></i> Registrar docente
        </a>
        @endif
        <a href="{{ route('asistencia.index') }}" class="{{ $route == 'asistencia.index' ? 'active' : '' }}">
            <i class="fa-solid fa-file-circle-plus"></i> Ver asistencias
        </a>
        @if(Auth::check() && Auth::user()->rol === 'prosecretario')
        <a href="{{ route('usuarios.index') }}" class="{{ $route == 'usuarios.index' ? 'active' : '' }}">
            <i class="fa-solid fa-user-shield"></i> Crear cuenta
        </a>
        @endif
        @if(Auth::check() && Auth::user()->rol === 'prosecretario')
        <a href="{{ route('docentes.buscar') }}" class="nav-item {{ request()->routeIs('docentes.buscar', 'docentes.edit') ? 'active' : '' }}">
            <i class="fa-solid fa-user-pen"></i> Editar docentes
        </a>
        @endif

        @auth
        <a href="#" class="nav-item" onclick="event.preventDefault(); abrirModalLogout();" style="display: inline-flex; align-items: center; gap: 8px; color: #ffffff; text-decoration: none; white-space: nowrap;">
            <i class="fa-solid fa-right-from-bracket" style="color: #ffffff; font-size: 1.2rem;"></i>
            <span>Cerrar sesión</span>
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
        @endauth
    </div>

    <div id="modal-logout" class="modal-logout-overlay">
    <div class="modal-logout-box">
        <div class="modal-logout-icon">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <h3 class="modal-logout-title">Cerrar sesión</h3>
        <p class="modal-logout-text">¿Está seguro de querer cerrar la sesión?</p>
        <div class="modal-logout-buttons">
            <button type="button" class="btn-modal-cancelar" onclick="cerrarModalLogout()">Cancelar</button>
            <button type="button" class="btn-modal-aceptar" onclick="confirmarLogout()">Aceptar</button>
        </div>
    </div>
</div>
</div>

<script>
    function abrirModalLogout() {
        document.getElementById('modal-logout').style.display = 'flex';
    }

    function cerrarModalLogout() {
        document.getElementById('modal-logout').style.display = 'none';
    }

    function confirmarLogout() {
        document.getElementById('logout-form').submit();
    }

    window.addEventListener('click', function(e) {
        const modal = document.getElementById('modal-logout');
        if (e.target === modal) {
            cerrarModalLogout();
        }
    });
</script>