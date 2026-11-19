<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Valores ISO-8601 (los mismos que devuelve Carbon::dayOfWeekIso): 1 = lunes ... 7 = domingo.
 * Reemplaza al texto "Lunes", "Miércoles"... que obligaba a normalizar tildes en cada comparación.
 */
enum DiaSemana: int
{
    case Lunes = 1;
    case Martes = 2;
    case Miercoles = 3;
    case Jueves = 4;
    case Viernes = 5;
    case Sabado = 6;
    case Domingo = 7;

    public function etiqueta(): string
    {
        return match ($this) {
            self::Lunes => 'Lunes',
            self::Martes => 'Martes',
            self::Miercoles => 'Miércoles',
            self::Jueves => 'Jueves',
            self::Viernes => 'Viernes',
            self::Sabado => 'Sábado',
            self::Domingo => 'Domingo',
        };
    }

    /** Días en los que se pueden cargar horarios. */
    public static function laborables(): array
    {
        return [self::Lunes, self::Martes, self::Miercoles, self::Jueves, self::Viernes];
    }

    public static function de(CarbonInterface $fecha): self
    {
        return self::from($fecha->dayOfWeekIso);
    }
}
