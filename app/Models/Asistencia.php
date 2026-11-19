<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asistencia extends Model
{
    protected $fillable = [
        'fecha', 'docente_id', 'horario_id', 'comando_id',
        'hora_ingreso', 'hora_egreso', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoAsistencia::class,
        ];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    /** Incluye horarios dados de baja, para no perder el historial. */
    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class)->withTrashed();
    }

    public function comando(): BelongsTo
    {
        return $this->belongsTo(Dy50Comando::class, 'comando_id');
    }

    public function ediciones(): HasMany
    {
        return $this->hasMany(AsistenciaEdicion::class);
    }
}
