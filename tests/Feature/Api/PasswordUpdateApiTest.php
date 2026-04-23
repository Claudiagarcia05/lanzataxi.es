<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_password_can_be_updated(): void
    {
        $usuario = User::factory()->create();

        Sanctum::actingAs($usuario);

        $respuesta = $this->putJson('/api/user/password', [
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ]);

        $respuesta
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertTrue(Hash::check('new-password', $usuario->refresh()->password));
    }
}
