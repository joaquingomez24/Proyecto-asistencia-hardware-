<?php

namespace App\Http\Controllers;

use App\Models\Dy50Comando;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Lo que el navegador consulta sobre el lector: si está conectado y cómo va una orden. */
class LectorController extends Controller
{
    public function enLinea(): JsonResponse
    {
        return response()->json([
            'en_linea' => Dy50Comando::lectorEnLinea(),
            'hace_segundos' => Dy50Comando::segundosDesdeContacto(),
        ]);
    }

    /** Estado de un comando. Solo lo puede ver quien lo pidió. */
    public function estado(Request $request, Dy50Comando $comando): JsonResponse
    {
        abort_unless($comando->user_id === $request->user()->id, 403);

        Dy50Comando::vencer();
        $comando->refresh();

        $respuesta = $comando->paraCliente() + ['cerrar_sesion' => false];

        // Docente que acaba de registrar su asistencia: se cierra su sesión en este mismo momento.
        // El navegador muestra el resultado unos segundos y vuelve al inicio de sesión.
        if ($comando->tipo === Dy50Comando::VERIFICAR
            && $comando->estado === Dy50Comando::COMPLETADO
            && $request->user()->esDocente()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $respuesta['cerrar_sesion'] = true;
            $respuesta['login_url'] = route('login');
        }

        return response()->json($respuesta);
    }

    public function cancelar(Request $request, Dy50Comando $comando): JsonResponse
    {
        abort_unless($comando->user_id === $request->user()->id, 403);

        $comando->finalizar(Dy50Comando::CANCELADO, 'Cancelado.');

        return response()->json($comando->paraCliente());
    }

    /** Pantalla de diagnóstico para el secretario. */
    public function diagnostico()
    {
        Dy50Comando::vencer();

        return view('lector.diagnostico', [
            'enLinea' => Dy50Comando::lectorEnLinea(),
            'segundos' => Dy50Comando::segundosDesdeContacto(),
            'dispositivo' => config('dy50.dispositivo'),
            'comandos' => Dy50Comando::query()->with(['usuario', 'docente'])->latest('id')->limit(20)->get(),
        ]);
    }
}
