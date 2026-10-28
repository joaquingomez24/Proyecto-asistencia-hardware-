<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $table = 'docentes';
    protected $primaryKey = 'id_docente';

    protected $fillable = [
        'DNI', 'nombre', 'apellido', 'telefono', 'email', 'id_huella', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function materias()
    {
        return $this->belongsToMany(
            Materia::class,
            'docentes_materias',
            'id_docente',
            'id_materias'
        );
    }

    public function huellas()
    {
        return $this->hasMany(Huella::class, 'id_docente', 'id_docente');
    }

    public function usuario()
    {
        return $this->hasOne(User::class, 'id_docente', 'id_docente');
    }
}
