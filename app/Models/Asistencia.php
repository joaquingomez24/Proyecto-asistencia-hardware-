<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencia';
    protected $primaryKey = 'id_asistencia';

    protected $fillable = [
        'fecha', 'id_docente', 'id_materia', 'dy50_event_id',
        'nombre_docente', 'materia', 'turno', 'hora_ingreso', 'hora_egreso', 'estado',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function materiaRelacionada()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materias');
    }
}
