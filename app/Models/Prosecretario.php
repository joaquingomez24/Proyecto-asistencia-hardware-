<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Prosecretario extends Authenticatable
{
    protected $table = 'prosecretario';
    protected $primaryKey = 'id_prosecretario';

    protected $fillable = [
        'usuario',
        'contraseña',
        'nombre',
        'apellido',
        'dni',
    ];

    public function getAuthPassword()
    {
        return $this->contraseña;
    }
}