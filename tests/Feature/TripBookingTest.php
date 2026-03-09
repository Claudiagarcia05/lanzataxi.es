<?php

    namespace Tests\Feature;

    use App\Models\Conductor;
    use App\Models\Taxi;
    use App\Models\Viaje;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;

    class TripBookingTest extends TestCase {
        use RefreshDatabase;

        public function test_user_can_book_a_viaje(): void {
            $user = User::factory()->create(['role' => 'pasajero']);

            Sanctum::actingAs($user);

            $response = $this->postJson('/api/viajes', [
                'pickup_lat' => 28.963,
                'pickup_lng' => -13.550,
                'dropoff_lat' => 28.978,
                'dropoff_lng' => -13.561,
            ]);

            $response->assertStatus(201)
                ->assertJsonStructure(['id', 'status', 'price']);
        }

        public function test_viaje_can_be_accepted_by_conductor(): void {
            $conductorUser = User::factory()->create(['role' => 'conductor']);
            $conductor = Conductor::factory()->create(['user_id' => $conductorUser->id, 'is_active' => true]);
            Taxi::factory()->create(['conductor_id' => $conductor->id]);

            $viaje = Viaje::factory()->create(['status' => 'pending']);

            Sanctum::actingAs($conductorUser);

            $response = $this->patchJson("/api/viajes/{$viaje->id}/accept");

            $response->assertStatus(200);
            $this->assertEquals('accepted', $viaje->fresh()->status);
        }
    }