@extends('layouts.app')

@section('title', 'Lector DY50')

@section('content')
<div class="card-consulta">
    <div class="header-consulta">
        <div class="icono-box"><i class="fa-solid fa-fingerprint"></i></div>
        <div>
            <h2>Lector de huellas (DY50)</h2>
            <p>Estado de la conexión con la Raspberry y últimas órdenes enviadas.</p>
        </div>
    </div>

    <div class="alerta {{ $enLinea ? 'exito' : 'error' }}">
        <i class="fa-solid {{ $enLinea ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
        @if ($enLinea)
            Dispositivo «{{ $dispositivo }}» conectado (último contacto hace {{ $segundos }} s).
        @elseif ($segundos !== null)
            Dispositivo «{{ $dispositivo }}» sin respuesta desde hace {{ $segundos }} s. Revisá que el agente esté corriendo en la Raspberry.
        @else
            Dispositivo «{{ $dispositivo }}» todavía no se conectó desde que se inició el servidor.
        @endif
    </div>

    <div class="tabla-scroll">
        <table>
            <thead>
                <tr><th>#</th><th>Hora</th><th>Acción</th><th>Pedida por</th><th>Docente</th><th>Estado</th><th>Detalle</th></tr>
            </thead>
            <tbody>
            @forelse ($comandos as $comando)
                <tr>
                    <td>{{ $comando->id }}</td>
                    <td>{{ $comando->created_at->format('d/m H:i:s') }}</td>
                    <td>{{ $comando->tipo }}</td>
                    <td>{{ $comando->usuario?->usuario ?? '—' }}</td>
                    <td>{{ $comando->docente?->nombre_completo ?? '—' }}</td>
                    <td>{{ $comando->estado }}</td>
                    <td>{{ $comando->mensaje ?? $comando->progreso ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align: center; color: #64748b;">Todavía no se envió ninguna orden al lector.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
