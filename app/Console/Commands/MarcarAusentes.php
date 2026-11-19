<?php

namespace App\Console\Commands;

use App\Enums\DiaSemana;
use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Horario;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;

class MarcarAusentes extends Command
{
    protected $signature = 'asistencia:marcar-ausentes';
    protected $description = 'Registra como ausentes las clases de hoy que ya terminaron sin asistencia cargada.';

    public function handle(): int
    {
        $ahora = now();
        $hoy = $ahora->toDateString();
        $creadas = 0;

        Horario::query()
            ->with('docente')
            ->where('dia_semana', DiaSemana::de($ahora)->value)
            ->where('hora_fin', '<=', $ahora->format('H:i:s'))
            ->whereHas('docente', fn ($q) => $q->where('activo', true))
            ->whereDoesntHave('asistencias', fn ($q) => $q->whereDate('fecha', $hoy))
            ->get()
            ->each(function (Horario $horario) use ($ahora, $hoy, &$creadas) {
                $inicio = $horario->inicioEn($ahora);
                $fin = $horario->finEn($ahora);

                // Horario inválido, o cargado después de que la clase ya había terminado: no corresponde ausencia.
                if ($fin->lessThanOrEqualTo($inicio) || $horario->created_at->greaterThan($fin)) {
                    return;
                }

                try {
                    Asistencia::create([
                        'fecha' => $hoy,
                        'docente_id' => $horario->docente_id,
                        'horario_id' => $horario->id,
                        'comando_id' => null,
                        'hora_ingreso' => null,
                        'hora_egreso' => null,
                        'estado' => EstadoAsistencia::Ausente,
                    ]);
                    $creadas++;
                } catch (UniqueConstraintViolationException) {
                    // Otro proceso la creó en el mismo instante: no hay nada que hacer.
                }
            });

        $this->info("Proceso finalizado. Ausencias creadas: {$creadas}.");

        return self::SUCCESS;
    }
}
