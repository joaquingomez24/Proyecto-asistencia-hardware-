<?php

namespace App\Models;

use App\Enums\Rol;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'usuario',
        'dni',
        'email',
        'password',
        'rol',
        'docente_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'rol' => Rol::class,
        ];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function esSecretario(): bool
    {
        return $this->rol === Rol::Secretario;
    }

    public function esJefe(): bool
    {
        return $this->rol === Rol::JefePreceptores;
    }

    public function esDocente(): bool
    {
        return $this->rol === Rol::Docente;
    }

    /** Quienes pueden consultar y editar el registro de asistencias. */
    public function puedeGestionarAsistencias(): bool
    {
        return $this->esSecretario() || $this->esJefe();
    }
}
