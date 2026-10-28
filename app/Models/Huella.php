<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Huella extends Model
{
    protected $table = 'huellas';
    protected $primaryKey = 'id_huella';

    protected $fillable = ['id_docente', 'sensor_id', 'dedo'];

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }
}
