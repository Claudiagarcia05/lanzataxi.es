<?php

    namespace Tests\Feature\Auth;

    use App\Models\User;
    use Illuminate\Auth\Notifications\ResetPassword;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Support\Facades\Notification;
    use Tests\TestCase;

    class PasswordResetTest extends TestCase {
        use RefreshDatabase;

        public function test_reset_password_link_screen_can_be_rendered(): void {
            $respuesta = $this->get('/forgot-password');

            $respuesta->assertStatus(200);
        }

        public function test_reset_password_link_can_be_requested(): void {
            Notification::fake();

            $usuario = User::factory()->create();

            $this->post('/forgot-password', ['email' => $usuario->email]);

            Notification::assertSentTo($usuario, ResetPassword::class);
        }

        public function test_reset_password_screen_can_be_rendered(): void {
            Notification::fake();

            $usuario = User::factory()->create();

            $this->post('/forgot-password', ['email' => $usuario->email]);

            Notification::assertSentTo($usuario, ResetPassword::class, function ($notification) {
                $respuesta = $this->get('/reset-password/'.$notification->token);

                $respuesta->assertStatus(200);

                return true;
            });
        }

        public function test_password_can_be_reset_with_valid_token(): void {
            Notification::fake();

            $usuario = User::factory()->create();

            $this->post('/forgot-password', ['email' => $usuario->email]);

            Notification::assertSentTo($usuario, ResetPassword::class, function ($notification) use ($usuario) {
                $respuesta = $this->post('/reset-password', [
                    'token' => $notification->token,
                    'email' => $usuario->email,
                    'password' => 'password',
                    'password_confirmation' => 'password',
                ]);

                $respuesta
                    ->assertSessionHasNoErrors()
                    ->assertRedirect(route('login'));

                return true;
            });
        }
    }