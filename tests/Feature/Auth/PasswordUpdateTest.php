<?php

    namespace Tests\Feature\Auth;

    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Support\Facades\Hash;
    use Tests\TestCase;

    class PasswordUpdateTest extends TestCase {
        use RefreshDatabase;

        public function test_password_can_be_updated(): void {
            $usuario = User::factory()->create();

            $respuesta = $this
                ->actingAs($usuario)
                ->from('/profile')
                ->put('/password', [
                    'current_password' => 'password',
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ]);

            $respuesta
                ->assertSessionHasNoErrors()
                ->assertRedirect('/profile');

            $this->assertTrue(Hash::check('new-password', $usuario->refresh()->password));
        }

        public function test_correct_password_must_be_provided_to_update_password(): void {
            $usuario = User::factory()->create();

            $respuesta = $this
                ->actingAs($usuario)
                ->from('/profile')
                ->put('/password', [
                    'current_password' => 'wrong-password',
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ]);

            $respuesta
                ->assertSessionHasErrors('current_password')
                ->assertRedirect('/profile');
        }
    }