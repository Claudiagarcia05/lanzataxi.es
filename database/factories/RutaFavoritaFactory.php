<?php

    namespace Database\Factories;

    use App\Models\RutaFavorita;
    use App\Models\User;
    use Illuminate\Database\Eloquent\Factories\Factory;

    /**
     * Factory de `RutaFavorita`.
     *
     * Genera una ubicación frecuente (casa/trabajo/etc.) asociada a un usuario.
     */
    class RutaFavoritaFactory extends Factory {
        protected $model = RutaFavorita::class;

        /**
         * Valores por defecto de una ruta favorita.
         */
        public function definition(): array {
            
            return [
                'user_id' => User::factory(),
                // Nombre corto para mostrar en UI.
                'name' => $this->faker->words(2, true),
                'address' => $this->faker->address(),
                'lat' => $this->faker->latitude(),
                'lng' => $this->faker->longitude(),
                // Orden de aparición (no necesariamente único).
                'order' => $this->faker->randomDigit(),
            ];
        }
    }