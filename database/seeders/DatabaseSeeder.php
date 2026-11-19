<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Crea la cuenta inicial del secretario con los datos de .env (no hay credenciales escritas en el código):
 *   ADMIN_USUARIO, ADMIN_DNI, ADMIN_EMAIL, ADMIN_PASSWORD
 * Las demás cuentas (jefes y docentes) las crea el secretario desde la página.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (! $password) {
            throw new RuntimeException(
                'Falta ADMIN_PASSWORD en .env. Definí ADMIN_USUARIO, ADMIN_DNI, ADMIN_EMAIL y ADMIN_PASSWORD antes de correr el seeder.'
            );
        }

        User::updateOrCreate(
            ['usuario' => env('ADMIN_USUARIO', 'secretaria')],
            [
                'dni' => env('ADMIN_DNI', '00000000'),
                'email' => env('ADMIN_EMAIL', 'secretaria@epet20.edu.ar'),
                'password' => $password,
                'rol' => Rol::Secretario,
            ]
        );
    }
}
