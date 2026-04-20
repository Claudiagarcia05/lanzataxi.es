<?php

    namespace Database\Factories;

    use App\Models\Conductor;
    use App\Models\User;
    use Illuminate\Database\Eloquent\Factories\Factory;

    /**
     * Factory de `Conductor`.
     *
     * Se usa para generar conductores de prueba/seed:
     * - Crea (por defecto) un `User` asociado con rol `conductor`.
     * - Genera un número de licencia único.
     * - Parte como inactivo (útil para flujos de aprobación).
     */
    class ConductorFactory extends Factory {
        protected $model = Conductor::class;

        /**
         * Valores por defecto del conductor.
         */
        public function definition(): array {
            return [
                // Asegura consistencia: el conductor debe apuntar a un usuario con rol conductor.
                'user_id' => User::factory()->state(['role' => 'conductor']),
                'license_number' => $this->faker->unique()->bothify('LIC-#####'),
                // Rating inicial (se recalcula con reseñas reales).
                'rating' => 5.0,
                // Inicia inactivo para simular flujo real (activación/aprobación).
                'is_active' => false,
            ];
        }
    }