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
                    'password' => 'NewPassword1!',
                    'password_confirmation' => 'NewPassword1!',
                ]);

            $respuesta
                ->assertSessionHasNoErrors()
                ->assertRedirect('/profile');

            $this->assertTrue(Hash::check('NewPassword1!', $usuario->refresh()->password));
        }

        public function test_correct_password_must_be_provided_to_update_password(): void {
            $usuario = User::factory()->create();

            $respuesta = $this
                ->actingAs($usuario)
                ->from('/profile')
                ->put('/password', [
                    'current_password' => 'wrong-password',
                    'password' => 'NewPassword1!',
                    'password_confirmation' => 'NewPassword1!',
                ]);

            $respuesta
                ->assertSessionHasErrors('current_password')
                ->assertRedirect('/profile');
        }
    }