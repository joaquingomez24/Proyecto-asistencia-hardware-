@extends('layouts.app')

@section('title', 'Sistema de Asistencia')

@section('content')
<main class="main-container">
    <div class="hero-circle">
        <i class="fa-solid fa-user-check"></i>
    </div>
    <h1>Sistema de asistencia docente</h1>
    <div class="underline"></div>
</main>

@if(session('exito'))
    <div class="alerta-flotante">
        <i class="fa-solid fa-circle-check"></i> {{ session('exito') }}
    </div>
@endif

<style>
    .alerta-flotante {
        position: fixed;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        background: transparent;
        color: #0f5132;
        border: none;
        box-shadow: none;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        z-index: 1000;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const alerta = document.querySelector('.alerta-flotante');
        if (alerta) {
            setTimeout(() => {
                alerta.style.transition = 'opacity 0.5s ease';
                alerta.style.opacity = '0';
                setTimeout(() => alerta.remove(), 500);
            }, 4000);
        }
    });
</script>
@endsection