<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HuellaController extends Controller
{
    public function registrar(Request $request)
    {
        // Validación de los datos enviados por Python
        $request->validate([
            'id_huella' => 'required|integer',
            'exito' => 'required|boolean'
        ]);

        if ($request->exito) {
            // Ejemplo: Guardar ID de huella temporal o asociar
            return response()->json([
                'status' => 'success',
                'message' => 'Huella recibida y procesada correctamente.',
                'id_huella' => $request->id_huella
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Error en la lectura del sensor.'
        ], 400);
    }
}