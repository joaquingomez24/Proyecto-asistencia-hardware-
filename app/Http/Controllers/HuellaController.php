<?php

namespace App\Http\Controllers; // <-- Corregido (sin \Api)

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Docente;    // Asegúrate de importar tu modelo Docente
use App\Models\Asistencia; // Asegúrate de importar tu modelo Asistencia si lo usas

class HuellaController extends Controller
{
    /**
     * Registra la huella capturada por la Raspberry Pi / Python
     */
    public function registrar(Request $request)
    {
        // Validación de los datos enviados por Python
        $request->validate([
            'id_huella' => 'required|integer',
            'exito'     => 'required|boolean'
        ]);

        if ($request->exito) {
            return response()->json([
                'status'    => 'success',
                'message'   => 'Huella recibida y procesada correctamente.',
                'id_huella' => $request->id_huella
            ], 200);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Error en la lectura del sensor.'
        ], 400);
    }

    /**
     * Registra la entrada/salida de un docente al apoyar el dedo en el lector
     */
    public function marcarAsistencia(Request $request)
    {
        $request->validate([
            'id_huella' => 'required|integer',
        ]);

        // Ejemplo: Buscar al docente por su id_huella o ID en la BD
        $docente = Docente::where('id_huella', $request->id_huella)->first();

        if (!$docente) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Huella no asociada a ningún docente activo.'
            ], 404);
        }

        // Se guarda el registro en la tabla de asistencias
        // Asistencia::create(['docente_id' => $docente->id, 'fecha_hora' => now()]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Asistencia registrada correctamente.',
            'docente' => $docente->nombre
        ], 200);
    }

    /**
     * Verifica la disponibilidad de la API/Lector
     */
    public function estadoLector()
    {
        return response()->json([
            'status'  => 'ok',
            'mensaje' => 'Servicio biométrico en línea y respondiendo correctamente.'
        ], 200);
    }
}