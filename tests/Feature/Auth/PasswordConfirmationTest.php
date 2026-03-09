<?php

    namespace Tests\Feature\Auth;

    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class PasswordConfirmationTest extends TestCase {
        use RefreshDatabase;

        public function test_confirm_password_screen_can_be_rendered(): void {
            $usuario = User::factory()->create();

            $respuesta = $this->actingAs($usuario)->get('/confirm-password');

            $respuesta->assertStatus(200);
        }

        public function test_password_can_be_confirmed(): void {
            $usuario = User::factory()->create();

            $respuesta = $this->actingAs($usuario)->post('/confirm-password', [
                'password' => 'password',
            ]);

            $respuesta->assertRedirect();
            $respuesta->assertSessionHasNoErrors();
        }

        public function test_password_is_not_confirmed_with_invalid_password(): void {
            $usuario = User::factory()->create();

            $respuesta = $this->actingAs($usuario)->post('/confirm-password', [
                'password' => 'wrong-password',
            ]);

            $respuesta->assertSessionHasErrors();
        }
    }