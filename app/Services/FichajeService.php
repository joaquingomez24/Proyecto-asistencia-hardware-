<?php

namespace App\Services;

use App\Enums\DiaSemana;
use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Horario;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Reglas para registrar la asistencia de un docente a un horario.
 * Antes estaban copiadas en tres lugares (controlador, tablero y comando de ausentes).
 */
class FichajeService
{
    /**
     * Verifica que el docente pueda registrar este horario ahora. Devuelve el horario o lanza FichajeException.
     */
    public function validar(Docente $docente, int $horarioId, ?CarbonInterface $ahora = null): Horario
    {
        $ahora ??= now();

        if (! $docente->activo) {
            throw new FichajeException('Tu ficha de docente está inactiva. Consultá a Secretaría.');
        }

        $horario = $docente->horarios()->with('materia')->find($horarioId);

        if (! $horario) {
            throw new FichajeException('La materia seleccionada no está asignada a tu usuario.');
        }

        if ($horario->dia_semana !== DiaSemana::de($ahora)) {
            throw new FichajeException('La materia seleccionada no corresponde al día de hoy.');
        }

        $inicio = $horario->inicioEn($ahora);
        $fin = $horario->finEn($ahora);

        if ($fin->lessThanOrEqualTo($inicio)) {
            throw new FichajeException('El horario de esta materia no es válido (el fin debe ser posterior al inicio). Avisá a Secretaría.');
        }

        $apertura = $inicio->copy()->subMinutes((int) config('asistencia.minutos_antes'));
        if ($ahora->lessThan($apertura)) {
            throw new FichajeException('Todavía no se puede registrar esta materia. Se habilita a las ' . $apertura->format('H:i') . '.');
        }

        if ($ahora->greaterThanOrEqualTo($fin)) {
            throw new FichajeException('El horario de esta materia ya terminó. Si no hubo registro, queda como ausente.');
        }

        $yaRegistrada = Asistencia::query()
            ->whereDate('fecha', $ahora->toDateString())
            ->where('docente_id', $docente->id)
            ->where('horario_id', $horario->id)
            ->exists();

        if ($yaRegistrada) {
            throw new FichajeException('Ya registraste tu asistencia para esta materia hoy.');
        }

        return $horario;
    }

    public function estadoPara(Horario $horario, CarbonInterface $ahora): EstadoAsistencia
    {
        $inicio = $horario->inicioEn($ahora);

        if ($ahora->lessThan($inicio)) {
            return EstadoAsistencia::Presente;
        }

        $limite = $inicio->copy()->addMinutes((int) config('asistencia.minutos_tolerancia'));

        return $ahora->lessThan($limite) ? EstadoAsistencia::SobreLaHora : EstadoAsistencia::Tarde;
    }

    /**
     * Revalida y guarda la asistencia. $comandoId es la lectura del DY50 que la respalda.
     */
    public function registrar(Docente $docente, int $horarioId, ?int $comandoId = null, ?CarbonInterface $ahora = null): Asistencia
    {
        $ahora ??= now();
        $horario = $this->validar($docente, $horarioId, $ahora);

        try {
            return Asistencia::create([
                'fecha' => $ahora->toDateString(),
                'docente_id' => $docente->id,
                'horario_id' => $horario->id,
                'comando_id' => $comandoId,
                'hora_ingreso' => $ahora->format('H:i:s'),
                'hora_egreso' => null,
                'estado' => $this->estadoPara($horario, $ahora),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new FichajeException('Ya registraste tu asistencia para esta materia hoy.');
        }
    }
}
