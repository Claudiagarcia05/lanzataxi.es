<?php

    namespace Database\Factories;

    use App\Models\Conductor;
    use App\Models\Taxi;
    use Illuminate\Database\Eloquent\Factories\Factory;

    /**
     * Factory de `Taxi`.
     *
     * Crea un taxi asociado a un conductor.
     * Se usa para poblar datos de prueba y validar relaciones conductor<->taxi.
     */
    class TaxiFactory extends Factory {
        protected $model = Taxi::class;

        /**
         * Valores por defecto del taxi.
         */
        public function definition(): array {
            
            return [
                // Crea automáticamente el conductor si no se proporciona.
                'conductor_id' => Conductor::factory(),
                // Matrícula ficticia; patrón simple para tests.
                'plate' => $this->faker->unique()->bothify('####-???'),
                'model' => $this->faker->word(),
                // Capacidad típica (pasajeros).
                'capacity' => 4,
                'color' => $this->faker->safeColorName(),
                // Estado inicial para simular disponibilidad.
                'status' => 'available',
            ];
        }
    }