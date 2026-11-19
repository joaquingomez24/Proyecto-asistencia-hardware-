<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Dy50Comando;
use App\Models\Huella;
use App\Services\FichajeException;
use App\Services\FichajeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * API que consume el agente de la Raspberry (protegida con DY50_TOKEN).
 *
 *   GET  /api/dy50/comandos/siguiente?dispositivo=raspberry-1   -> toma la próxima orden (204 si no hay)
 *   POST /api/dy50/comandos/{id}/progreso                       -> texto de avance; responde el estado actual
 *   POST /api/dy50/comandos/{id}/resultado                      -> resultado final de la orden
 */
class Dy50Controller extends Controller
{
    public function __construct(private FichajeService $fichaje) {}

    public function siguiente(Request $request): JsonResponse|Response
    {
        $dispositivo = (string) $request->query('dispositivo', config('dy50.dispositivo'));

        Dy50Comando::marcarContacto($dispositivo);
        Dy50Comando::vencer();

        $candidato = Dy50Comando::query()
            ->where('dispositivo', $dispositivo)
            ->where('estado', Dy50Comando::PENDIENTE)
            ->where('expira_at', '>=', now())
            ->orderBy('id')
            ->first();

        if (! $candidato) {
            return response()->noContent();
        }

        // Toma atómica: si otro proceso lo tomó primero, no se actualiza ninguna fila.
        $tomado = Dy50Comando::query()
            ->whereKey($candidato->id)
            ->where('estado', Dy50Comando::PENDIENTE)
            ->update([
                'estado' => Dy50Comando::EN_CURSO,
                'reclamado_at' => now(),
                'expira_at' => now()->addSeconds($candidato->timeout_segundos + 10),
                'updated_at' => now(),
            ]);

        if ($tomado !== 1) {
            return response()->noContent();
        }

        return response()->json([
            'id' => $candidato->id,
            'tipo' => $candidato->tipo,
            'sensor_id' => $candidato->sensor_id,
            'timeout_segundos' => $candidato->timeout_segundos,
        ]);
    }

    public function progreso(Request $request, Dy50Comando $comando): JsonResponse
    {
        $datos = $request->validate(['mensaje' => ['nullable', 'string', 'max:160']]);

        Dy50Comando::vencer();

        $actualizacion = [
            'expira_at' => now()->addSeconds($comando->timeout_segundos + 10),
            'updated_at' => now(),
        ];
        if (isset($datos['mensaje'])) {
            $actualizacion['progreso'] = $datos['mensaje'];
        }

        Dy50Comando::query()
            ->whereKey($comando->id)
            ->where('estado', Dy50Comando::EN_CURSO)
            ->update($actualizacion);

        // El agente corta lo que esté haciendo si el estado ya no es "en_curso" (cancelado o vencido).
        return response()->json(['ok' => true, 'estado' => $comando->fresh()->estado]);
    }

    public function resultado(Request $request, Dy50Comando $comando): JsonResponse
    {
        $datos = $request->validate([
            'exito' => ['required', 'boolean'],
            'sensor_id' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'confianza' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'mensaje' => ['nullable', 'string', 'max:255'],
        ]);

        Dy50Comando::vencer();
        $comando->refresh();

        if ($comando->estado !== Dy50Comando::EN_CURSO) {
            return response()->json(['ok' => false, 'mensaje' => "El comando ya está {$comando->estado}."], 409);
        }

        match ($comando->tipo) {
            Dy50Comando::VERIFICAR => $this->cerrarVerificacion($comando, $datos),
            Dy50Comando::ENROLAR => $this->cerrarEnrolamiento($comando, $datos),
            default => $comando->finalizar(
                $datos['exito'] ? Dy50Comando::COMPLETADO : Dy50Comando::ERROR,
                $datos['mensaje'] ?? null
            ),
        };

        return response()->json(['ok' => true, 'estado' => $comando->estado]);
    }

    /** La lectura debe ser de una huella vinculada al docente que pidió el registro. */
    private function cerrarVerificacion(Dy50Comando $comando, array $datos): void
    {
        if (! $datos['exito']) {
            $comando->finalizar(Dy50Comando::ERROR, $datos['mensaje'] ?? 'No se pudo leer la huella. Volvé a intentarlo.');

            return;
        }

        $extra = ['sensor_id' => $datos['sensor_id'] ?? null, 'confianza' => $datos['confianza'] ?? null];
        $docente = Docente::find($comando->docente_id);

        $vinculada = $docente
            && isset($datos['sensor_id'])
            && Huella::where('docente_id', $docente->id)->where('sensor_id', $datos['sensor_id'])->exists();

        if (! $vinculada) {
            $comando->finalizar(Dy50Comando::ERROR, 'La huella leída no corresponde a tu usuario. No se registró la asistencia.', $extra);

            return;
        }

        try {
            DB::transaction(function () use ($comando, $docente, $extra) {
                $asistencia = $this->fichaje->registrar($docente, (int) $comando->horario_id, $comando->id);
                $materia = $asistencia->horario->materia->nombre;

                $comando->finalizar(
                    Dy50Comando::COMPLETADO,
                    "Asistencia registrada: {$materia} a las ".substr((string) $asistencia->hora_ingreso, 0, 5)
                        .". Estado: {$asistencia->estado->etiqueta()}.",
                    $extra
                );
            });
        } catch (FichajeException $e) {
            $comando->finalizar(Dy50Comando::ERROR, $e->getMessage(), $extra);
        }
    }

    private function cerrarEnrolamiento(Dy50Comando $comando, array $datos): void
    {
        if (! $datos['exito']) {
            $comando->finalizar(Dy50Comando::ERROR, $datos['mensaje'] ?? 'No se pudo registrar la huella.');

            return;
        }

        DB::transaction(function () use ($comando) {
            Huella::updateOrCreate(
                ['sensor_id' => $comando->sensor_id],
                ['docente_id' => $comando->docente_id, 'dedo' => $comando->dedo]
            );

            $comando->finalizar(Dy50Comando::COMPLETADO, 'Huella registrada y vinculada al docente.');
        });
    }
}
