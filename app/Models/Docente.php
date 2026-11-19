<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Docente extends Model
{
    protected $fillable = ['dni', 'nombre', 'apellido', 'telefono', 'email', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class);
    }

    public function huellas(): HasMany
    {
        return $this->hasMany(Huella::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** "Apellido, Nombre" */
    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => $this->apellido . ', ' . $this->nombre);
    }
}
