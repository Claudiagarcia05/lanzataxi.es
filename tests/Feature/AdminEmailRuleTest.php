<?php

    namespace Tests\Feature;

    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class AdminEmailRuleTest extends TestCase {
        use RefreshDatabase;

        private const MENSAJE_CREDENCIALES_INVALIDAS = 'Credenciales inválidas. Por favor verifica tu email y contraseña.';

        public function test_admin_role_requires_admin_email_domain(): void {
            $response = $this->postJson('/api/register', [
                'name' => 'Admin Test',
                'email' => 'admin.test@gmail.com',
                'password' => 'secret12',
                'password_confirmation' => 'secret12',
                'role' => 'admin',
            ]);

            $response
                ->assertStatus(422)
                ->assertJsonValidationErrors(['email'])
                ->assertJsonPath('message', self::MENSAJE_CREDENCIALES_INVALIDAS)
                ->assertJsonPath('errors.email.0', self::MENSAJE_CREDENCIALES_INVALIDAS);
        }

        public function test_admin_email_domain_requires_admin_role(): void {
            $response = $this->postJson('/api/register', [
                'name' => 'Passenger Test',
                'email' => 'passenger.test@admin.com',
                'password' => 'secret12',
                'password_confirmation' => 'secret12',
                'role' => 'pasajero',
            ]);

            $response
                ->assertStatus(422)
                ->assertJsonValidationErrors(['role'])
                ->assertJsonPath('message', self::MENSAJE_CREDENCIALES_INVALIDAS)
                ->assertJsonPath('errors.role.0', self::MENSAJE_CREDENCIALES_INVALIDAS);
        }
    }