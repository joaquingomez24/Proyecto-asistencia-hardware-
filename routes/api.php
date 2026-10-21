<?
use App\Http\Controllers\Api\HuellaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::post('/huellas/registrar', [HuellaController::class, 'registrar']);

Route::post('/dy50/eventos', function (Request $request) {
    $tokenEsperado = config('dy50.api_token');
    $tokenRecibido = $request->bearerToken();

    if (!is_string($tokenEsperado) || $tokenEsperado === '' ||
        !is_string($tokenRecibido) || !hash_equals($tokenEsperado, $tokenRecibido)) {
        return response()->json(['mensaje' => 'No autorizado'], 401);
    }

    $datos = $request->validate([
        'fingerprint_id' => ['required', 'integer', 'min:0', 'max:65535'],
        'confidence' => ['nullable', 'integer', 'min:0', 'max:65535'],
    ]);

    $idEvento = DB::table('dy50_events')->insertGetId([
        'fingerprint_id' => $datos['fingerprint_id'],
        'confidence' => $datos['confidence'] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json([
        'ok' => true,
        'mensaje' => 'Evento DY50 recibido',
        'evento_id' => $idEvento,
        'fingerprint_id' => $datos['fingerprint_id'],
    ], 201);
});

Route::get('/dy50/eventos', function () {
    $eventos = DB::table('dy50_events')
        ->orderByDesc('id')
        ->limit(30)
        ->get(['id', 'fingerprint_id', 'confidence', 'created_at']);

    return response()->json($eventos);
});

?>