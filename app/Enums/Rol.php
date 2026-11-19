<?php

namespace App\Enums;

enum Rol: string
{
    case Secretario = 'secretario';
    case JefePreceptores = 'jefe_preceptores';
    case Docente = 'docente';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Secretario => 'Secretario',
            self::JefePreceptores => 'Jefe de preceptores',
            self::Docente => 'Docente',
        };
    }
}
