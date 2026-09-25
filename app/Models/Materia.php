<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materias';
    protected $primaryKey = 'id_materias';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'turno',
        'curso',
        'division',
        'dia',
        'horario_inicio',
        'horario_finalizacion',
    ];
}
