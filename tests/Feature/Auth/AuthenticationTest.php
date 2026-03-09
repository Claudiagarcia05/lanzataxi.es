<?php

    namespace Tests\Feature\Auth;

    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class AuthenticationTest extends TestCase {
        use RefreshDatabase;

        public function test_login_screen_can_be_rendered(): void {
            $respuesta = $this->get('/login');

            $respuesta->assertStatus(200);
        }

        public function test_users_can_authenticate_using_the_login_screen(): void {
            $usuario = User::factory()->create();

            $respuesta = $this->post('/login', [
                'email' => $usuario->email,
                'password' => 'password',
            ]);

            $this->assertAuthenticated();
            $respuesta->assertRedirect(route('dashboard', absolute: false));
        }

        public function test_users_can_not_authenticate_with_invalid_password(): void {
            $usuario = User::factory()->create();

            $this->post('/login', [
                'email' => $usuario->email,
                'password' => 'wrong-password',
            ]);

            $this->assertGuest();
        }

        public function test_users_can_logout(): void {
            $usuario = User::factory()->create();

            $respuesta = $this->actingAs($usuario)->post('/logout');

            $this->assertGuest();
            $respuesta->assertRedirect('/');
        }
    }