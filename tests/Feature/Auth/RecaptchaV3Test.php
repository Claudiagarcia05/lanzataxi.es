<?php

    namespace Tests\Feature\Auth;

    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Support\Facades\Http;
    use Tests\TestCase;

    class RecaptchaV3Test extends TestCase {
        use RefreshDatabase;

        public function test_api_login_requires_recaptcha_token_when_enabled(): void {
            config([
                'recaptcha.enabled' => true,
                'recaptcha.secret_key' => 'test-secret',
                'recaptcha.actions.login' => 'login',
            ]);

            $usuario = User::factory()->create();

            Http::fake();

            $this->postJson('/api/login', [
                'email' => $usuario->email,
                'password' => 'password',
            ])->assertStatus(422)
                ->assertJsonValidationErrors('recaptcha_token');
        }

        public function test_api_login_fails_when_recaptcha_score_is_too_low(): void {
            config([
                'recaptcha.enabled' => true,
                'recaptcha.secret_key' => 'test-secret',
                'recaptcha.min_score' => 0.5,
                'recaptcha.hostname' => '',
                'recaptcha.actions.login' => 'login',
            ]);

            $usuario = User::factory()->create();

            Http::fake([
                'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                    'success' => true,
                    'score' => 0.1,
                    'action' => 'login',
                    'hostname' => 'localhost',
                ], 200),
            ]);

            $this->postJson('/api/login', [
                'email' => $usuario->email,
                'password' => 'password',
                'recaptcha_token' => 'token',
            ])->assertStatus(422)
                ->assertJsonValidationErrors('recaptcha');
        }

        public function test_api_login_succeeds_when_recaptcha_is_valid(): void {
            config([
                'recaptcha.enabled' => true,
                'recaptcha.secret_key' => 'test-secret',
                'recaptcha.min_score' => 0.5,
                'recaptcha.hostname' => '',
                'recaptcha.actions.login' => 'login',
            ]);

            $usuario = User::factory()->create();

            Http::fake([
                'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                    'success' => true,
                    'score' => 0.9,
                    'action' => 'login',
                    'hostname' => 'localhost',
                ], 200),
            ]);

            $respuesta = $this->postJson('/api/login', [
                'email' => $usuario->email,
                'password' => 'password',
                'recaptcha_token' => 'token',
            ]);

            $respuesta->assertStatus(200)
                ->assertJsonStructure([
                    'token',
                    'user' => ['id', 'name', 'email', 'role'],
                    'message',
                ]);
        }
    }
