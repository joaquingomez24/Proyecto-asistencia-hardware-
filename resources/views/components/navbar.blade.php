@php $usuario = Auth::user(); @endphp

<div class="navbar">
    <div class="nav-brand">
        <img src="{{ asset('images/logo_epet.png') }}" alt="Epet N 20" class="nav-logo">
        <div class="nav-title">
            <h2>Epet N° 20</h2>
            <span>Sistema de asistencia</span>
        </div>
    </div>

    <div class="nav-links">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i> Inicio
        </a>

        @if ($usuario?->puedeGestionarAsistencias())
            <a href="{{ route('asistencias.index') }}" class="{{ request()->routeIs('asistencias.*') ? 'active' : '' }}">
                <i class="fa-solid fa-clipboard-list"></i> Asistencias
            </a>
        @endif

        @if ($usuario?->esSecretario())
            <a href="{{ route('docentes.create') }}" class="{{ request()->routeIs('docentes.create') ? 'active' : '' }}">
                <i class="fa-solid fa-user-plus"></i> Registrar docente
            </a>
            <a href="{{ route('docentes.index') }}" class="{{ request()->routeIs('docentes.index', 'docentes.edit') ? 'active' : '' }}">
                <i class="fa-solid fa-user-pen"></i> Docentes
            </a>
            <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                <i class="fa-solid fa-user-shield"></i> Cuentas
            </a>
            <a href="{{ route('lector.diagnostico') }}" class="{{ request()->routeIs('lector.diagnostico') ? 'active' : '' }}">
                <i class="fa-solid fa-fingerprint"></i> Lector
            </a>
        @endif

        @auth
            <a href="#" onclick="event.preventDefault(); abrirModalLogout();">
                <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        @endauth
    </div>
</div>

@auth
    <div id="modal-logout" class="modal-logout-overlay">
        <div class="modal-logout-box">
            <div class="modal-logout-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
            <h3 class="modal-logout-title">Cerrar sesión</h3>
            <p class="modal-logout-text">¿Está seguro de querer cerrar la sesión?</p>
            <div class="modal-logout-buttons">
                <button type="button" class="btn-modal-cancelar" onclick="cerrarModalLogout()">Cancelar</button>
                <button type="button" class="btn-modal-aceptar" onclick="document.getElementById('logout-form').submit()">Aceptar</button>
            </div>
        </div>
    </div>

    <script>
        function abrirModalLogout() { document.getElementById('modal-logout').style.display = 'flex'; }
        function cerrarModalLogout() { document.getElementById('modal-logout').style.display = 'none'; }
        window.addEventListener('click', function (e) {
            if (e.target === document.getElementById('modal-logout')) { cerrarModalLogout(); }
        });
    </script>
@endauth
