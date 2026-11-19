<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretario_can_create_another_secretary_account(): void
    {
        $secretario = User::create([
            'usuario' => 'secretaria-inicial',
            'dni' => '10000001',
            'email' => 'inicial@example.test',
            'password' => 'clave-inicial-segura',
            'rol' => Rol::Secretario,
        ]);

        $this->actingAs($secretario)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('Secretario')
            ->assertDontSee('Prosecretario');

        $this->actingAs($secretario)
            ->post(route('usuarios.store'), [
                'rol' => Rol::Secretario->value,
                'usuario' => 'secretaria-secundaria',
                'dni' => '10000002',
                'email' => 'secundaria@example.test',
                'password' => 'clave-secundaria-segura',
                'password_confirmation' => 'clave-secundaria-segura',
            ])
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('users', [
            'usuario' => 'secretaria-secundaria',
            'dni' => '10000002',
            'rol' => Rol::Secretario->value,
            'docente_id' => null,
        ]);
    }
}
