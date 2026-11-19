@extends('layouts.app')

@section('title', 'Registrar docente')

@section('content')
<div class="card-consulta">
    <div class="header-consulta">
        <div class="icono-box"><i class="fa-solid fa-user-plus"></i></div>
        <div>
            <h2>Registrar docente</h2>
            <p>Cargá los datos personales y los horarios. La huella se registra después, desde la ficha del docente.</p>
        </div>
    </div>

    <x-errores />

    @include('docentes._form', [
        'docente' => new \App\Models\Docente(),
        'accion' => route('docentes.store'),
        'metodo' => null,
        'boton' => 'Guardar docente',
    ])
</div>
@endsection
