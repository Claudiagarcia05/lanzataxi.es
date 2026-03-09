<?php

    namespace Tests\Feature;

    use App\Models\Conductor;
    use App\Models\Taxi;
    use App\Models\Viaje;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;

    class ReservaViajeTest extends TestCase {
        use RefreshDatabase;

        public function test_user_can_book_a_viaje(): void {
            $usuario = User::factory()->create(['role' => 'pasajero']);

            Sanctum::actingAs($usuario);

            $respuesta = $this->postJson('/api/viajes', [
                'pickup_lat' => 28.963,
                'pickup_lng' => -13.550,
                'dropoff_lat' => 28.978,
                'dropoff_lng' => -13.561,
            ]);

            $respuesta->assertStatus(201)
                ->assertJsonStructure(['id', 'status', 'price']);
        }

        public function test_viaje_can_be_accepted_by_conductor(): void {
            $conductorUser = User::factory()->create(['role' => 'conductor']);
            $conductor = Conductor::factory()->create(['user_id' => $conductorUser->id, 'is_active' => true]);
            Taxi::factory()->create(['conductor_id' => $conductor->id]);

            $viaje = Viaje::factory()->create(['status' => 'pending']);

            Sanctum::actingAs($conductorUser);

            $respuesta = $this->patchJson("/api/viajes/{$viaje->id}/accept");

            $respuesta->assertStatus(200);
            $this->assertEquals('accepted', $viaje->fresh()->status);
        }
    }