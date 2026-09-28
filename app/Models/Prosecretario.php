<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Prosecretario extends Authenticatable
{
    protected $table = 'prosecretario';
    protected $primaryKey = 'id_prosecretario';

    protected $fillable = [
        'rol',
        'usuario',
        'password',
        'dni',
        'email'

    ];

    public function getAuthPassword()
    {
        return $this->password;
    }
}