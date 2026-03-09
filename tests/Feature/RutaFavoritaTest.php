<?php

    namespace Tests\Feature;

    use App\Models\User;
    use App\Models\RutaFavorita;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class RutaFavoritaTest extends TestCase {
        use RefreshDatabase;

        public function test_get_favorites_unauthenticated() {
            $response = $this->getJson('/api/favorites');
            
            $response->assertUnauthorized();
        }

        public function test_get_favorites_authenticated() {
            $user = User::factory()->create();
            
            RutaFavorita::factory(3)->create(['user_id' => $user->id]);
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/favorites');
            
            $response->assertOk();
            $response->assertJsonCount(3);
        }

        public function test_store_favorite() {
            $user = User::factory()->create();
            
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/favorites', [
                    'name' => 'Mi casa',
                    'address' => 'Calle 123',
                    'lat' => 10.123456,
                    'lng' => -75.654321,
                ]);
            
            $response->assertCreated();
            $this->assertDatabaseHas('rutas_favoritas', [
                'user_id' => $user->id,
                'name' => 'Mi casa',
            ]);
        }

        public function test_get_favorites_returns_ordered() {
            $user = User::factory()->create();
            
            RutaFavorita::create([
                'user_id' => $user->id,
                'name' => 'Favorito 1',
                'address' => 'Dirección 1',
                'lat' => 10.0,
                'lng' => -75.0,
                'order' => 2,
            ]);
            
            RutaFavorita::create([
                'user_id' => $user->id,
                'name' => 'Favorito 2',
                'address' => 'Dirección 2',
                'lat' => 11.0,
                'lng' => -76.0,
                'order' => 1,
            ]);
            
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/favorites');
            
            $response->assertOk();
            $favorites = $response->json();
            
            $this->assertEquals(1, $favorites[0]['order']);
            $this->assertEquals(2, $favorites[1]['order']);
        }
    }