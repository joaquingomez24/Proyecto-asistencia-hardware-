<?php

namespace App\Services;

use App\Models\Docente;
use App\Models\Horario;
use App\Models\Materia;
use Illuminate\Support\Facades\DB;

/**
 * Alta, modificación y baja de los horarios de un docente, y detección de solapamientos.
 * El formulario envía la lista COMPLETA de horarios del docente: los que traen "id" se actualizan,
 * los nuevos se crean y los que ya no vienen se dan de baja (sin perder su historial).
 */
class HorarioService
{
    /**
     * Devuelve una lista de mensajes de error (vacía si no hay conflictos).
     *
     * @param  array<int, array<string, mixed>>  $filas
     * @return string[]
     */
    public function conflictos(array $filas, ?Docente $docente = null): array
    {
        $errores = [];
        $filas = array_values($filas);

        // 1) Entre las filas del mismo formulario.
        foreach ($filas as $i => $a) {
            foreach ($filas as $j => $b) {
                if ($j <= $i || (int) $a['dia_semana'] !== (int) $b['dia_semana']) {
                    continue;
                }

                if (! $this->seSolapan($a['hora_inicio'], $a['hora_fin'], $b['hora_inicio'], $b['hora_fin'])) {
                    continue;
                }

                $dia = \App\Enums\DiaSemana::from((int) $a['dia_semana'])->etiqueta();
                $errores[] = "El docente no puede dictar dos materias a la vez: {$dia} de {$a['hora_inicio']} a {$a['hora_fin']} se superpone con {$b['hora_inicio']} a {$b['hora_fin']}.";
            }
        }

        // 2) Contra horarios de otros docentes: un curso no puede tener dos profesores a la misma hora.
        foreach ($filas as $fila) {
            $choque = Horario::query()
                ->with(['docente', 'materia'])
                ->when($docente, fn ($q) => $q->where('docente_id', '!=', $docente->id))
                ->where('curso', $fila['curso'])
                ->where('division', $fila['division'])
                ->where('dia_semana', (int) $fila['dia_semana'])
                ->where('hora_inicio', '<', $fila['hora_fin'] . ':00')
                ->where('hora_fin', '>', $fila['hora_inicio'] . ':00')
                ->first();

            if ($choque) {
                $errores[] = "El curso {$fila['curso']} {$fila['division']} ya tiene a {$choque->docente->nombre_completo} "
                    . "({$choque->materia->nombre}) el {$choque->dia_semana->etiqueta()} de {$choque->rango()}.";
            }
        }

        return array_values(array_unique($errores));
    }

    /**
     * Sincroniza los horarios del docente con la lista enviada.
     *
     * @param  array<int, array<string, mixed>>  $filas
     */
    public function sincronizar(Docente $docente, array $filas): void
    {
        DB::transaction(function () use ($docente, $filas) {
            $idsConservados = [];

            foreach ($filas as $fila) {
                $materia = Materia::firstOrCreate(['nombre' => trim($fila['materia'])]);

                $datos = [
                    'materia_id' => $materia->id,
                    'curso' => $fila['curso'],
                    'division' => $fila['division'],
                    'turno' => $fila['turno'],
                    'dia_semana' => (int) $fila['dia_semana'],
                    'hora_inicio' => $fila['hora_inicio'],
                    'hora_fin' => $fila['hora_fin'],
                ];

                $horario = ! empty($fila['id'])
                    ? $docente->horarios()->find($fila['id'])
                    : null;

                if ($horario) {
                    $horario->update($datos);
                } else {
                    $horario = $docente->horarios()->create($datos);
                }

                $idsConservados[] = $horario->id;
            }

            // Los que ya no están en el formulario se dan de baja (soft delete).
            $docente->horarios()->whereNotIn('id', $idsConservados)->get()->each->delete();
        });
    }

    private function seSolapan(string $inicioA, string $finA, string $inicioB, string $finB): bool
    {
        return $inicioA < $finB && $finA > $inicioB;
    }
}
