<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Huella;

class HuellasTableSeeder extends Seeder
{
    public function run(): void
    {
        Huella::create([
            'id_docente' => 1,
            'sensor_id' => 101,
            'dedo' => 'Índice Derecho',
        ]);
    }
}