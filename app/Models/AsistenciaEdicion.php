<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaEdicion extends Model
{
    protected $table = 'asistencia_ediciones';

    protected $fillable = ['asistencia_id', 'user_id', 'antes', 'despues'];

    protected function casts(): array
    {
        return ['antes' => 'array', 'despues' => 'array'];
    }

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
