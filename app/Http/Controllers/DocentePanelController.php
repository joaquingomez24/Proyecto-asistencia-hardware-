<?php

namespace App\Http\Controllers;

use App\Enums\DiaSemana;
use App\Models\Asistencia;
use App\Models\Dy50Comando;
use App\Services\FichajeException;
use App\Services\FichajeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Pantalla del docente (se usa desde la Raspberry): elegir la materia de hoy y registrar la asistencia con el dedo. */
class DocentePanelController extends Controller
{
    public function __construct(private FichajeService $fichaje)
    {
    }

    public function index(Request $request)
    {
        $docente = $request->user()->docente;
        abort_unless($docente, 403, 'Tu cuenta no está vinculada a un docente. Consultá a Secretaría.');

        $ahora = now();

        $horarios = $docente->horarios()
            ->with('materia')
            ->where('dia_semana', DiaSemana::de($ahora)->value)
            ->orderBy('hora_inicio')
            ->get();

        $registradas = Asistencia::query()
            ->whereDate('fecha', $ahora->toDateString())
            ->where('docente_id', $docente->id)
            ->get()
            ->keyBy('horario_id');

        // Para cada horario: ¿se puede registrar ahora y por qué no?
        $situaciones = $horarios->mapWithKeys(function ($horario) use ($docente, $ahora, $registradas) {
            if ($registradas->has($horario->id)) {
                return [$horario->id => ['registrable' => false, 'texto' => 'Registrada: ' . $registradas[$horario->id]->estado->etiqueta()]];
            }

            try {
                $this->fichaje->validar($docente, $horario->id, $ahora);

                return [$horario->id => ['registrable' => true, 'texto' => 'Disponible para registrar']];
            } catch (FichajeException $e) {
                return [$horario->id => ['registrable' => false, 'texto' => $e->getMessage()]];
            }
        });

        return view('docente.panel', [
            'docente' => $docente,
            'horarios' => $horarios,
            'situaciones' => $situaciones,
            'tieneHuella' => $docente->huellas()->exists(),
            'ahora' => $ahora,
        ]);
    }

    /**
     * Valida la materia elegida y le ordena al lector que espere el dedo.
     * La asistencia se guarda cuando el lector informa el resultado (ver Api\Dy50Controller).
     */
    public function iniciar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'horario_id' => ['required', 'integer'],
        ]);

        $docente = $request->user()->docente;
        abort_unless($docente, 403);

        try {
            $horario = $this->fichaje->validar($docente, (int) $datos['horario_id']);
        } catch (FichajeException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        if (! $docente->huellas()->exists()) {
            return response()->json(['mensaje' => 'Todavía no tenés una huella registrada. Consultá a Secretaría.'], 422);
        }

        // Si había un intento anterior abierto de esta misma persona, se descarta.
        Dy50Comando::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('estado', [Dy50Comando::PENDIENTE, Dy50Comando::EN_CURSO])
            ->update(['estado' => Dy50Comando::CANCELADO, 'finalizado_at' => now(), 'updated_at' => now()]);

        $comando = Dy50Comando::nuevo(Dy50Comando::VERIFICAR, (int) config('dy50.timeout_verificar'), [
            'user_id' => $request->user()->id,
            'docente_id' => $docente->id,
            'horario_id' => $horario->id,
            'progreso' => 'Esperando al lector…',
        ]);

        return response()->json(['comando_id' => $comando->id], 201);
    }
}
