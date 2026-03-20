<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Viaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminClientTripsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_client_trips_report_with_trips(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@admin.es',
        ]);

        $pasajero = User::factory()->create([
            'role' => 'pasajero',
            'email' => 'cliente@example.com',
        ]);

        Viaje::factory()->create([
            'pasajero_id' => $pasajero->id,
            'status' => 'completed',
            'price' => 12.50,
        ]);

        Viaje::factory()->create([
            'pasajero_id' => $pasajero->id,
            'status' => 'cancelled',
            'price' => 0,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/admin/clients/{$pasajero->id}/trips-report");

        $response
            ->assertOk()
            ->assertJsonPath('client.id', $pasajero->id)
            ->assertJsonCount(2, 'trips');
    }
}
