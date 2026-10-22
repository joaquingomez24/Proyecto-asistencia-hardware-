<?php
use App\Http\Controllers\HuellaController;
use Illuminate\Support\Facades\Route;

Route::post('/huellas/registrar', [HuellaController::class, 'registrar']);
\Illuminate\Support\Facades\Route::post('/dy50/eventos', function (\Illuminate\Http\Request $request) {
    $esperado = (string) config('dy50.token');
    $recibido = (string) $request->bearerToken();

    if ($esperado === '' || ! hash_equals($esperado, $recibido)) {
        return response()->json(['ok' => false, 'mensaje' => 'No autorizado'], 401);
    }

    $validador = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'fingerprint_id' => ['required', 'integer', 'min:0', 'max:65535'],
        'confidence'     => ['required', 'integer', 'min:0', 'max:65535'],
        'device'         => ['nullable', 'string', 'max:50'],
    ]);

    if ($validador->fails()) {
        return response()->json([
            'ok'      => false,
            'mensaje' => 'Datos inválidos',
            'errores' => $validador->errors(),
        ], 422);
    }

    $datos = $validador->validated();

    $id = \Illuminate\Support\Facades\DB::table('dy50_events')->insertGetId([
        'fingerprint_id' => $datos['fingerprint_id'],
        'confidence'     => $datos['confidence'],
        'device'         => $datos['device'] ?? null,
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    return response()->json([
        'ok'             => true,
        'mensaje'        => 'Evento DY50 recibido',
        'id'             => $id,
        'fingerprint_id' => $datos['fingerprint_id'],
        'confidence'     => $datos['confidence'],
    ], 201);
});

/*
 * Lista los 30 eventos más recientes (la usa la página de pruebas).
 * No lleva token: el proxy HTTPS en modo "api" NO expone esta ruta a la
 * red; solo se llega desde la propia PC (http://127.0.0.1:8000).
 */
\Illuminate\Support\Facades\Route::get('/dy50/eventos', function () {
    $eventos = \Illuminate\Support\Facades\DB::table('dy50_events')
        ->orderByDesc('id')
        ->limit(30)
        ->get(['id', 'fingerprint_id', 'confidence', 'device', 'created_at']);

    return response()->json(['ok' => true, 'eventos' => $eventos]);
});
