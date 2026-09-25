<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'jose gomez',
            'email' => 'josegomez@hotmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('jose323')
        ]);
    }
}