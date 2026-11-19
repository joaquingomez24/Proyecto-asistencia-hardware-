<?php

namespace App\Http\Controllers;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\AsistenciaEdicion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Consulta y corrección del registro de asistencias (secretario y jefe de preceptores). */
class AsistenciaController extends Controller
{
    private const REGEX_HORA = '/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'fecha' => ['nullable', 'date'],
            'docente' => ['nullable', 'string', 'max:100'],
        ]);

        $asistencias = Asistencia::query()
            ->with(['docente', 'horario.materia'])
            ->when($filtros['fecha'] ?? null, fn ($q, $fecha) => $q->whereDate('fecha', $fecha))
            ->when($filtros['docente'] ?? null, function ($q, $texto) {
                $q->whereHas('docente', fn ($d) => $d
                    ->where('nombre', 'like', "%{$texto}%")
                    ->orWhere('apellido', 'like', "%{$texto}%"));
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora_ingreso')
            ->paginate(15)
            ->withQueryString();

        return view('asistencias.index', compact('asistencias'));
    }

    public function edit(Asistencia $asistencia)
    {
        $asistencia->load(['docente', 'horario.materia']);

        // Solo se puede reasignar a otro horario del mismo docente (más el actual, aunque esté dado de baja).
        $horarios = $asistencia->docente->horarios()->with('materia')->get();
        if (! $horarios->contains('id', $asistencia->horario_id)) {
            $horarios->push($asistencia->horario);
        }

        return view('asistencias.edit', [
            'asistencia' => $asistencia,
            'horarios' => $horarios,
            'estados' => EstadoAsistencia::cases(),
        ]);
    }

    public function update(Request $request, Asistencia $asistencia)
    {
        $datos = $request->validate([
            'horario_id' => ['required', 'integer', Rule::exists('horarios', 'id')->where('docente_id', $asistencia->docente_id)],
            'hora_ingreso' => ['nullable', 'regex:' . self::REGEX_HORA],
            'hora_egreso' => ['nullable', 'regex:' . self::REGEX_HORA],
            'estado' => ['required', Rule::enum(EstadoAsistencia::class)],
        ]);

        $duplicada = Asistencia::query()
            ->where('id', '!=', $asistencia->id)
            ->whereDate('fecha', $asistencia->fecha->toDateString())
            ->where('docente_id', $asistencia->docente_id)
            ->where('horario_id', $datos['horario_id'])
            ->exists();

        if ($duplicada) {
            return back()->withInput()->withErrors([
                'horario_id' => 'Ese docente ya tiene una asistencia cargada para esa materia en esa fecha.',
            ]);
        }

        $antes = $this->instantanea($asistencia);

        $asistencia->update([
            'horario_id' => (int) $datos['horario_id'],
            'hora_ingreso' => $this->normalizarHora($datos['hora_ingreso'] ?? null),
            'hora_egreso' => $this->normalizarHora($datos['hora_egreso'] ?? null),
            'estado' => $datos['estado'],
        ]);

        $despues = $this->instantanea($asistencia->refresh());

        if ($antes !== $despues) {
            AsistenciaEdicion::create([
                'asistencia_id' => $asistencia->id,
                'user_id' => $request->user()->id,
                'antes' => $antes,
                'despues' => $despues,
            ]);
        }

        return redirect()->route('asistencias.index')->with('exito', 'Asistencia actualizada correctamente.');
    }

    /** Valores editables, normalizados, para comparar y guardar en la bitácora. */
    private function instantanea(Asistencia $a): array
    {
        return [
            'horario_id' => (int) $a->horario_id,
            'hora_ingreso' => $a->hora_ingreso ? substr((string) $a->hora_ingreso, 0, 8) : null,
            'hora_egreso' => $a->hora_egreso ? substr((string) $a->hora_egreso, 0, 8) : null,
            'estado' => $a->estado->value,
        ];
    }

    private function normalizarHora(?string $hora): ?string
    {
        if ($hora === null || $hora === '') {
            return null;
        }

        return strlen($hora) === 5 ? $hora . ':00' : $hora;
    }
}
