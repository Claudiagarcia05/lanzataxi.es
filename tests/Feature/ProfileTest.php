<?php

    namespace Tests\Feature;

    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class ProfileTest extends TestCase {
        use RefreshDatabase;

        public function test_profile_page_is_displayed(): void {
            $usuario = User::factory()->create();

            $respuesta = $this
                ->actingAs($usuario)
                ->get('/profile');

            $respuesta->assertOk();
        }

        public function test_profile_information_can_be_updated(): void {
            $usuario = User::factory()->create();

            $respuesta = $this
                ->actingAs($usuario)
                ->patch('/profile', [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ]);

            $respuesta
                ->assertSessionHasNoErrors()
                ->assertRedirect('/profile');

            $usuario->refresh();

            $this->assertSame('Test User', $usuario->name);
            $this->assertSame('test@example.com', $usuario->email);
            $this->assertNull($usuario->email_verified_at);
        }

        public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void {
            $usuario = User::factory()->create();

            $respuesta = $this
                ->actingAs($usuario)
                ->patch('/profile', [
                    'name' => 'Test User',
                    'email' => $usuario->email,
                ]);

            $respuesta
                ->assertSessionHasNoErrors()
                ->assertRedirect('/profile');

            $this->assertNotNull($usuario->refresh()->email_verified_at);
        }

        public function test_user_can_delete_their_account(): void {
            $usuario = User::factory()->create();

            $respuesta = $this
                ->actingAs($usuario)
                ->delete('/profile', [
                    'password' => 'password',
                ]);

            $respuesta
                ->assertSessionHasNoErrors()
                ->assertRedirect('/');

            $this->assertGuest();
            $this->assertNull($usuario->fresh());
        }

        public function test_correct_password_must_be_provided_to_delete_account(): void {
            $usuario = User::factory()->create();

            $respuesta = $this
                ->actingAs($usuario)
                ->from('/profile')
                ->delete('/profile', [
                    'password' => 'wrong-password',
                ]);

            $respuesta
                ->assertSessionHasErrors('password')
                ->assertRedirect('/profile');

            $this->assertNotNull($usuario->fresh());
        }
    }