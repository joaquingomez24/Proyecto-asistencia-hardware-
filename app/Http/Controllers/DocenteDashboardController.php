<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocenteDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->rol === 'docente', 403);

        $docente = $user->docente;
        if (!$docente) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'error' => 'La cuenta no está vinculada a un docente. Pedile a Secretaría que revise el usuario.',
            ]);
        }

        $ahora = Carbon::now(config('app.timezone'));
        $hoy = $ahora->toDateString();
        $dia = [
            0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
        ][$ahora->dayOfWeek];
        $normalizar = static fn (?string $value): string => Str::lower(Str::ascii(trim((string) $value)));

        $materiasHoy = $docente->materias()
            ->get()
            ->filter(fn ($materia) => $normalizar($materia->dia) === $normalizar($dia))
            ->sortBy('horario_inicio')
            ->values();

        $nombreCompleto = trim($docente->nombre . ' ' . $docente->apellido);
        $asistenciasHoy = Asistencia::query()
            ->whereDate('fecha', $hoy)
            ->where(function ($query) use ($docente, $nombreCompleto) {
                $query->where('id_docente', $docente->id_docente)
                    ->orWhere('nombre_docente', $nombreCompleto);
            })
            ->get();

        $asistenciasPorMateria = $asistenciasHoy
            ->filter(fn ($asistencia) => $asistencia->id_materia !== null)
            ->keyBy('id_materia');

        $ultimoEventoId = (int) (DB::table('dy50_events')->max('id') ?? 0);
        $request->session()->put('dy50_baseline_event_id', $ultimoEventoId);

        return view('docente.dashboard', compact(
            'docente', 'materiasHoy', 'asistenciasPorMateria', 'ultimoEventoId', 'ahora'
        ));
    }
}
