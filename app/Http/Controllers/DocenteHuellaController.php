<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Dy50Comando;
use App\Models\Huella;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Registro (enrolamiento) y baja de huellas de un docente, ordenados desde la página al DY50. */
class DocenteHuellaController extends Controller
{
    /**
     * Pide al lector que registre un dedo nuevo. Responde con el id del comando para seguir el avance.
     */
    public function enrolar(Request $request, Docente $docente): JsonResponse
    {
        $datos = $request->validate([
            'dedo' => ['nullable', 'string', 'max:40'],
        ]);

        $sensorId = $this->proximoSensorLibre();

        if ($sensorId === null) {
            return response()->json(['mensaje' => 'El lector no tiene más posiciones libres. Eliminá huellas que ya no se usen.'], 422);
        }

        // Un solo registro en curso por persona.
        Dy50Comando::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('estado', [Dy50Comando::PENDIENTE, Dy50Comando::EN_CURSO])
            ->update(['estado' => Dy50Comando::CANCELADO, 'finalizado_at' => now(), 'updated_at' => now()]);

        $comando = Dy50Comando::nuevo(Dy50Comando::ENROLAR, (int) config('dy50.timeout_enrolar'), [
            'user_id' => $request->user()->id,
            'docente_id' => $docente->id,
            'sensor_id' => $sensorId,
            'dedo' => $datos['dedo'] ?? null,
        ]);

        return response()->json(['comando_id' => $comando->id], 201);
    }

    /** Quita el vínculo y le pide al lector que borre la plantilla de esa posición. */
    public function destroy(Docente $docente, Huella $huella)
    {
        Dy50Comando::nuevo(Dy50Comando::BORRAR, 10, [
            'user_id' => auth()->id(),
            'docente_id' => $docente->id,
            'sensor_id' => $huella->sensor_id,
        ]);

        $huella->delete();

        return back()->with('exito', 'Huella desvinculada. El lector borrará la plantilla si está conectado.');
    }

    /** Menor posición del sensor que no esté vinculada ni reservada por otro registro en curso. */
    private function proximoSensorLibre(): ?int
    {
        $usadas = Huella::query()->pluck('sensor_id')
            ->merge(
                Dy50Comando::query()
                    ->where('tipo', Dy50Comando::ENROLAR)
                    ->whereIn('estado', [Dy50Comando::PENDIENTE, Dy50Comando::EN_CURSO])
                    ->where('expira_at', '>', now())
                    ->pluck('sensor_id')
            )
            ->all();

        for ($posicion = 1; $posicion <= (int) config('dy50.capacidad'); $posicion++) {
            if (! in_array($posicion, $usadas, true)) {
                return $posicion;
            }
        }

        return null;
    }
}
