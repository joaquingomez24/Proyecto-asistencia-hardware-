<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Docente; // Importante: Referencia al modelo Docente

class DocenteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Docente::create([
            'DNI' => '48512687',
            'nombre' => 'Guadalupe',
            'apellido' => 'Medel',
            'telefono' => '2147483647',
            'email' => 'guada@gmail.com',
            'id_huella' => '1',
        ]);

        Docente::create([
            'DNI' => '48749707',
            'nombre' => 'Sofia',
            'apellido' => 'Lencina',
            'telefono' => '2994108659',
            'email' => 'sofiaepet20@gmail.com',
            'id_huella' => '1248858'
        ]);

        Docente::create([
            'DNI' => '48795812',
            'nombre' => 'Sofia',
            'apellido' => 'Lencina',
            'telefono' => '2994567891',
            'email' => 'sofiaepet20@gmail.com',
            'id_huella' => '27585'
        ]);

        Docente::create([
            'DNI' => '48749708',
            'nombre' => 'Malena',
            'apellido' => 'Viscardi',
            'telefono' => '2994517896',
            'email' => 'male.viscardi@gmail.com',
            'id_huella' => '70725'
        ]);
    }
}
