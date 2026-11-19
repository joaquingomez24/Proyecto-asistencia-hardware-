<?php

namespace App\Enums;

enum EstadoAsistencia: string
{
    case Presente = 'presente';
    case SobreLaHora = 'sobre_la_hora';
    case Tarde = 'tarde';
    case Ausente = 'ausente';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Presente => 'Presente',
            self::SobreLaHora => 'Sobre la hora',
            self::Tarde => 'Tarde',
            self::Ausente => 'Ausente',
        };
    }
}
