<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario secretario / prosecretario
        User::updateOrCreate(
            ['rol' => 'secretario'],
            [
                'usuario'=>'juan',
                'dni'=>'1234',
                'email' => 'secretario@epet20.edu.ar',
                'password' => Hash::make('epet20'),
                'rol' => 'prosecretario',
            ]
        );

        // Usuario jefe
        User::updateOrCreate(
            ['rol' => 'jefe'],
            [
                'usuario'=>'juan',
                'dni'=>'1234',
                'email' => 'jefe@epet20.edu.ar',
                'password' => Hash::make('epet20'),
                'rol' => 'jefe_preceptores',
            ]
        );
    }
}