<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_another_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@admin.es',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/admins', [
            'name' => 'Nuevo Admin',
            'email' => 'nuevo@admin.es',
            'password' => 'secret12',
            'password_confirmation' => 'secret12',
            'phone' => '+34 600 444 444',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('user.email', 'nuevo@admin.es')
            ->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@admin.es',
            'role' => 'admin',
        ]);
    }

    public function test_admin_cannot_create_admin_with_admin_dot_com_domain(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@admin.es',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/admins', [
            'name' => 'Admin Malo',
            'email' => 'malo@admin.com',
            'password' => 'secret12',
            'password_confirmation' => 'secret12',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseMissing('users', [
            'email' => 'malo@admin.com',
            'role' => 'admin',
        ]);
    }
}
