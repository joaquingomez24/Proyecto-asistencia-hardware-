<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencia';
    protected $primaryKey = 'id_asistencia';

    protected $fillable = [
        'fecha',
        'nombre_docente',
        'materia',
        'turno',
        'hora_ingreso',
        'hora_egreso',
        'estado',
    ];
}
