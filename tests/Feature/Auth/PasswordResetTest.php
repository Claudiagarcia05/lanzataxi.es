<?php

    namespace Tests\Feature\Auth;

    use App\Models\User;
    use App\Services\PasswordRecoveryService;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\Hash;
    use Tests\TestCase;

    class PasswordResetTest extends TestCase {
        use RefreshDatabase;

        public function test_reset_password_link_screen_can_be_rendered(): void {
            $respuesta = $this->get('/forgot-password');

            $respuesta->assertStatus(200);
        }

        public function test_reset_password_link_can_be_requested(): void {
            $usuario = User::factory()->create();

            $respuesta = $this->post('/forgot-password', ['email' => $usuario->email]);

            $respuesta->assertStatus(200);
            $respuesta->assertJson(['message' => 'Si el correo existe, hemos enviado un código de verificación.']);
        }

        public function test_reset_password_screen_can_be_rendered(): void {
            $usuario = User::factory()->create();
            $email = strtolower(trim($usuario->email));
            $code = '12345';

            Cache::put('password-recovery:'.$email, [
                'email' => $email,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'verified' => false,
                'verify_token' => null,
                'requested_at' => now()->toIso8601String(),
            ], now()->addMinutes(15));

            $respuesta = $this->post('/forgot-password/verify', [
                'email' => $email,
                'code' => $code,
            ]);

            $respuesta->assertStatus(200);
            $respuesta->assertJsonStructure(['message', 'token']);
        }

        public function test_password_can_be_reset_with_valid_token(): void {
            $usuario = User::factory()->create();
            $email = strtolower(trim($usuario->email));
            $token = \Illuminate\Support\Str::random(64);

            Cache::put('password-recovery:'.$email, [
                'email' => $email,
                'code_hash' => Hash::make('12345'),
                'attempts' => 0,
                'verified' => true,
                'verify_token' => $token,
                'requested_at' => now()->toIso8601String(),
                'verified_at' => now()->toIso8601String(),
            ], now()->addMinutes(15));

            $respuesta = $this->post('/forgot-password/reset', [
                'email' => $email,
                'token' => $token,
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

            $respuesta->assertStatus(200);
            $respuesta->assertJson(['message' => 'Contraseña actualizada correctamente.']);
            $this->assertTrue(Hash::check('NewPassword1!', $usuario->refresh()->password));
        }
    }