<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Huella extends Model
{
    protected $fillable = ['docente_id', 'sensor_id', 'dedo'];

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }
}
