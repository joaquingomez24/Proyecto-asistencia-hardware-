<?php

use App\Http\Controllers\Api\Dy50Controller;
use App\Http\Middleware\VerificarTokenDy50;
use Illuminate\Support\Facades\Route;

/*
 * API para el agente de la Raspberry (lector DY50). Todo exige "Authorization: Bearer <DY50_TOKEN>".
 * Ya no hay rutas públicas que listen lecturas: el navegador consulta por sesión (ver routes/web.php).
 */
Route::middleware(VerificarTokenDy50::class)->prefix('dy50/comandos')->group(function () {
    Route::get('/siguiente', [Dy50Controller::class, 'siguiente']);
    Route::post('/{comando}/progreso', [Dy50Controller::class, 'progreso']);
    Route::post('/{comando}/resultado', [Dy50Controller::class, 'resultado']);
});
