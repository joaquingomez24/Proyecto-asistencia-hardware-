<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Huella;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocenteHuellaController extends Controller
{
    /**
     * Vincula un ID que ya fue enrolado físicamente en el DY50 con un docente.
     * No recibe ni almacena plantilla biométrica.
     */
    public function store(Request $request, Docente $docente)
    {
        abort_unless($request->user()?->rol === 'prosecretario', 403);

        $datos = $request->validate([
            'sensor_id' => ['required', 'integer', 'min:0', 'max:65535'],
            'dedo' => ['nullable', 'string', 'max:40'],
        ]);

        $sensorId = (int) $datos['sensor_id'];
        $existente = Huella::where('sensor_id', $sensorId)->first();

        if ($existente && (int) $existente->id_docente !== (int) $docente->id_docente) {
            return back()->withErrors([
                'huella' => 'Ese ID del lector ya está vinculado a otro docente. No se modificó la asignación.',
            ]);
        }

        DB::transaction(function () use ($docente, $sensorId, $datos) {
            Huella::updateOrCreate(
                ['sensor_id' => $sensorId],
                ['id_docente' => $docente->id_docente, 'dedo' => $datos['dedo'] ?? null]
            );

            // Conserva compatibilidad con el código heredado que consultaba docentes.id_huella.
            if ($docente->id_huella === null || $docente->id_huella === '') {
                $docente->id_huella = (string) $sensorId;
                $docente->save();
            }
        });

        return back()->with('exito', 'El ID del lector quedó vinculado al docente. La plantilla sigue almacenada en el sensor.');
    }
}
