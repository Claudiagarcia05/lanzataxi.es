<?php

    namespace Database\Factories;

    use Illuminate\Database\Eloquent\Factories\Factory;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Str;

    /**
     * Factory de usuarios.
     *
     * Genera usuarios válidos para tests/seed con:
     * - email verificado por defecto
     * - contraseña conocida ("password") hasheada una sola vez (cacheada)
     * - rol por defecto `pasajero`
     */
    class UserFactory extends Factory {
        /**
         * Cachea el hash para no recalcularlo por cada usuario generado.
         */
        protected static ?string $password;

        /**
         * Valores por defecto del usuario.
         */
        public function definition(): array {

            return [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                // Para tests suele convenir partir con email verificado.
                'email_verified_at' => now(),
                // NOTA: "password" es una contraseña de desarrollo/tests.
                'password' => static::$password ??= Hash::make('password'),
                'remember_token' => Str::random(10),
                // Rol por defecto. Otros factories pueden sobreescribirlo con state().
                'role' => 'pasajero',
            ];
        }

        /**
         * Estado: usuario sin email verificado.
         */
        public function unverified(): static {

            return $this->state(fn (array $attributes) => [
                'email_verified_at' => null,
            ]);
        }
    }