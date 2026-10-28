<?php

namespace App\Console\Commands;

use App\Models\Asistencia;
use App\Models\Docente;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarcarAusentes extends Command
{
    protected $signature = 'asistencia:marcar-ausentes';
    protected $description = 'Registra como ausentes las materias finalizadas sin fichaje.';

    public function handle(): int
    {
        $ahora = Carbon::now(config('app.timezone'));
        $hoy = $ahora->toDateString();
        $diaHoy = [
            0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
        ][$ahora->dayOfWeek];
        $normalizar = static fn (?string $value): string => Str::lower(Str::ascii(trim((string) $value)));
        $creadas = 0;

        $docentes = Docente::query()->with('materias')->get();

        foreach ($docentes as $docente) {
            $nombreCompleto = trim($docente->nombre . ' ' . $docente->apellido);

            foreach ($docente->materias as $materia) {
                if ($normalizar($materia->dia) !== $normalizar($diaHoy)) {
                    continue;
                }

                $inicio = Carbon::parse($hoy . ' ' . $materia->horario_inicio, config('app.timezone'));
                $fin = Carbon::parse($hoy . ' ' . $materia->horario_finalizacion, config('app.timezone'));

                if ($fin->lessThanOrEqualTo($inicio) || $fin->greaterThan($ahora)) {
                    continue;
                }

                $yaExiste = Asistencia::query()
                    ->whereDate('fecha', $hoy)
                    ->where(function ($query) use ($docente, $materia, $nombreCompleto) {
                        $query->where(function ($subquery) use ($docente, $materia) {
                            $subquery->where('id_docente', $docente->id_docente)
                                ->where('id_materia', $materia->id_materias);
                        })->orWhere(function ($subquery) use ($nombreCompleto, $materia) {
                            $subquery->where('nombre_docente', $nombreCompleto)
                                ->where('materia', $materia->nombre);
                        });
                    })
                    ->exists();

                if ($yaExiste) {
                    continue;
                }

                DB::transaction(function () use ($docente, $materia, $hoy, $nombreCompleto) {
                    Asistencia::create([
                        'fecha' => $hoy,
                        'id_docente' => $docente->id_docente,
                        'id_materia' => $materia->id_materias,
                        'dy50_event_id' => null,
                        'nombre_docente' => $nombreCompleto,
                        'materia' => $materia->nombre,
                        'turno' => $materia->turno,
                        'hora_ingreso' => null,
                        'hora_egreso' => null,
                        'estado' => 'Ausente',
                    ]);
                });

                $creadas++;
            }
        }

        $this->info("Proceso finalizado. Ausencias creadas: {$creadas}.");

        return self::SUCCESS;
    }
}
