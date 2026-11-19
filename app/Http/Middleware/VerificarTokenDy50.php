<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Protege la API que usa la Raspberry: exige "Authorization: Bearer <DY50_TOKEN>". */
class VerificarTokenDy50
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperado = (string) config('dy50.token');
        $recibido = (string) $request->bearerToken();

        if ($esperado === '' || ! hash_equals($esperado, $recibido)) {
            return response()->json(['ok' => false, 'mensaje' => 'No autorizado'], 401);
        }

        return $next($request);
    }
}
