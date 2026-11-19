<?php

namespace App\Models;

use App\Enums\DiaSemana;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Horario extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'docente_id', 'materia_id', 'curso', 'division', 'turno',
        'dia_semana', 'hora_inicio', 'hora_fin',
    ];

    protected function casts(): array
    {
        return ['dia_semana' => DiaSemana::class];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    /** Momento de inicio de la clase en la fecha dada (zona horaria de la app). */
    public function inicioEn(CarbonInterface $fecha): Carbon
    {
        return Carbon::parse($fecha->toDateString() . ' ' . $this->hora_inicio, config('app.timezone'));
    }

    public function finEn(CarbonInterface $fecha): Carbon
    {
        return Carbon::parse($fecha->toDateString() . ' ' . $this->hora_fin, config('app.timezone'));
    }

    /** "08:00 a 09:20" */
    public function rango(): string
    {
        return substr((string) $this->hora_inicio, 0, 5) . ' a ' . substr((string) $this->hora_fin, 0, 5);
    }

    /** "Matemática — 3° 1° (Mañana)" */
    public function descripcion(): string
    {
        return "{$this->materia->nombre} — {$this->curso} {$this->division} ({$this->turno})";
    }
}
