<?php

    namespace Database\Factories;

    use App\Models\Viaje;
    use App\Models\User;
    use Illuminate\Database\Eloquent\Factories\Factory;

    /**
     * Factory de `Viaje`.
     *
     * Genera un viaje en estado inicial `pending`:
     * - Con pasajero creado (rol `pasajero`).
     * - Sin conductor/taxi asignados (se asignan al aceptar el viaje).
     * - Coordenadas dentro de un rango concreto (útil para tests geográficos).
     */
    class ViajeFactory extends Factory {
        protected $model = Viaje::class;

        /**
         * Valores por defecto del viaje.
         */
        public function definition(): array {

            return [
                // El viaje debe pertenecer a un pasajero.
                'pasajero_id' => User::factory()->state(['role' => 'pasajero']),
                // Se asignan cuando un conductor acepta.
                'conductor_id' => null,
                'taxi_id' => null,
                // Rango de coordenadas: ajustado al área prevista por la app.
                'pickup_lat' => $this->faker->latitude(28.8, 29.1),
                'pickup_lng' => $this->faker->longitude(-13.8, -13.4),
                'dropoff_lat' => $this->faker->latitude(28.8, 29.1),
                'dropoff_lng' => $this->faker->longitude(-13.8, -13.4),
                // Estado inicial: pendiente de asignación.
                'status' => 'pending',
                // Se calculan al completar o al estimar la ruta.
                'distance' => null,
                'price' => null,
                'co2_saved' => null,
            ];
        }
    }