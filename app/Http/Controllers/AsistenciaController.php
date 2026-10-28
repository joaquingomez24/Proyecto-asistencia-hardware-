<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Materia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()?->rol, ['prosecretario', 'jefe_preceptores'], true), 403);

        $query = Asistencia::query();

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->input('fecha'));
        }

        if ($request->filled('docente')) {
            $query->where('nombre_docente', 'like', '%' . $request->input('docente') . '%');
        }

        $asistencias = $query
            ->orderByDesc('fecha')
            ->orderByDesc('hora_ingreso')
            ->paginate(10)
            ->withQueryString();

        return view('asistencia.index', compact('asistencias'));
    }

    public function create(Request $request)
    {
        abort_unless(in_array($request->user()?->rol, ['prosecretario', 'jefe_preceptores'], true), 403);

        return view('asistencia.registrar');
    }

    /**
     * Registra una sola entrada para la materia seleccionada por el docente.
     * La lectura debe corresponder a un evento DY50 recién recibido.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->rol === 'docente', 403);

        $datos = $request->validate([
            'id_materia' => ['required', 'integer', 'min:1'],
            'codigo_huella' => ['required', 'integer', 'min:0', 'max:65535'],
            'dy50_event_id' => ['required', 'integer', 'min:1'],
        ]);

        $docente = $user->docente;
        if (!$docente) {
            return back()->withErrors([
                'asistencia' => 'La cuenta no está vinculada a un docente. Pedile a Secretaría que revise la cuenta.',
            ]);
        }

        $ahora = Carbon::now(config('app.timezone'));
        $hoy = $ahora->toDateString();
        $diaActual = $this->nombreDia($ahora);
        $codigoHuella = (string) $datos['codigo_huella'];

        $evento = DB::table('dy50_events')
            ->where('id', $datos['dy50_event_id'])
            ->where('fingerprint_id', (int) $codigoHuella)
            ->where('created_at', '>=', $ahora->copy()->subSeconds(45))
            ->first();

        $eventoBase = (int) $request->session()->get('dy50_baseline_event_id', 0);
        if (!$evento || (int) $evento->id <= $eventoBase) {
            return back()->withErrors([
                'asistencia' => 'La lectura del DY50 ya venció, no es nueva para esta sesión o no coincide. Volvé a apoyar el dedo en el lector.',
            ]);
        }

        if (Asistencia::where('dy50_event_id', $evento->id)->exists()) {
            return back()->withErrors([
                'asistencia' => 'Esa lectura del DY50 ya fue utilizada. Volvé a apoyar el dedo en el lector.',
            ]);
        }

        $huellaValida = $docente->id_huella !== null
            && (string) $docente->id_huella === $codigoHuella;

        if (!$huellaValida) {
            $huellaValida = $docente->huellas()
                ->where('sensor_id', (int) $codigoHuella)
                ->exists();
        }

        if (!$huellaValida) {
            return back()->withErrors([
                'asistencia' => 'La huella leída no está vinculada a tu cuenta. No se registró la asistencia.',
            ]);
        }

        $materia = $docente->materias()
            ->where('materias.id_materias', $datos['id_materia'])
            ->first();

        if (!$materia || $this->normalizar($materia->dia) !== $this->normalizar($diaActual)) {
            return back()->withErrors([
                'asistencia' => 'La materia seleccionada no está asignada a tu usuario para el día de hoy.',
            ]);
        }

        $inicio = Carbon::parse($hoy . ' ' . $materia->horario_inicio, config('app.timezone'));
        $fin = Carbon::parse($hoy . ' ' . $materia->horario_finalizacion, config('app.timezone'));

        // Los horarios que cruzan la medianoche no se suponen válidos silenciosamente.
        if ($fin->lessThanOrEqualTo($inicio)) {
            return back()->withErrors([
                'asistencia' => 'El horario de esta materia no es válido (el fin debe ser posterior al inicio). Avisá a Secretaría.',
            ]);
        }

        $inicioPermitido = $inicio->copy()->subMinutes(20);
        if ($ahora->lessThan($inicioPermitido)) {
            return back()->withErrors([
                'asistencia' => 'Todavía no se puede fichar esta materia. La ventana se abre 20 minutos antes del inicio.',
            ]);
        }

        if ($ahora->greaterThanOrEqualTo($fin)) {
            return back()->withErrors([
                'asistencia' => 'El horario de esta materia ya terminó. Si no hubo fichaje, debe quedar como ausente.',
            ]);
        }

        $nombreCompleto = trim($docente->nombre . ' ' . $docente->apellido);

        $yaExiste = Asistencia::query()
            ->whereDate('fecha', $hoy)
            ->where(function ($query) use ($docente, $materia, $nombreCompleto) {
                $query->where(function ($subquery) use ($docente, $materia) {
                    $subquery->where('id_docente', $docente->id_docente)
                        ->where('id_materia', $materia->id_materias);
                })->orWhere(function ($subquery) use ($nombreCompleto, $materia) {
                    // Compatibilidad con filas antiguas que todavía no tienen las nuevas claves.
                    $subquery->where('nombre_docente', $nombreCompleto)
                        ->where('materia', $materia->nombre);
                });
            })
            ->exists();

        if ($yaExiste) {
            return back()->withErrors([
                'asistencia' => 'Ya existe una asistencia para esta materia en el día de hoy. No se registró otra entrada.',
            ]);
        }

        if ($ahora->lessThan($inicio)) {
            $estado = 'Presente';
        } elseif ($ahora->lessThanOrEqualTo($inicio->copy()->addMinutes(19)->endOfMinute())) {
            $estado = 'Sobre la hora';
        } else {
            $estado = 'Tarde';
        }

        DB::transaction(function () use ($docente, $materia, $evento, $hoy, $nombreCompleto, $ahora, $estado) {
            Asistencia::create([
                'fecha' => $hoy,
                'id_docente' => $docente->id_docente,
                'id_materia' => $materia->id_materias,
                'dy50_event_id' => $evento->id,
                'nombre_docente' => $nombreCompleto,
                'materia' => $materia->nombre,
                'turno' => $materia->turno,
                'hora_ingreso' => $ahora->format('H:i:s'),
                'hora_egreso' => null,
                'estado' => $estado,
            ]);
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(
            'exito',
            "Asistencia registrada para {$materia->nombre} a las " . $ahora->format('H:i:s') . ". Estado: {$estado}. La sesión se cerró automáticamente."
        );
    }

    public function edit(Request $request)
    {
        abort_unless($request->user()?->rol === 'jefe_preceptores', 403);

        $id = $request->query('id');
        abort_if(!$id, 404);

        $asistencia = Asistencia::findOrFail($id);
        $materias = Materia::query()->select('nombre')->distinct()->orderBy('nombre')->get();

        return view('asistencia.edit', compact('asistencia', 'materias'));
    }

    public function update(Request $request)
    {
        abort_unless($request->user()?->rol === 'jefe_preceptores', 403);

        $datos = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
            'materia' => ['required', 'string', 'max:255'],
            'entrada' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'salida' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'estado' => ['required', 'in:Presente,Sobre la hora,Tarde,Ausente'],
        ]);

        $asistencia = Asistencia::findOrFail($datos['id']);
        $asistencia->update([
            'materia' => $datos['materia'],
            'hora_ingreso' => $datos['entrada'] ?? null,
            'hora_egreso' => $datos['salida'] ?? null,
            'estado' => $datos['estado'],
        ]);

        return redirect()->route('asistencia.index')->with('exito', 'Asistencia actualizada correctamente.');
    }

    private function nombreDia(Carbon $fecha): string
    {
        return [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
        ][$fecha->dayOfWeek];
    }

    private function normalizar(?string $texto): string
    {
        return Str::lower(Str::ascii(trim((string) $texto)));
    }
}
