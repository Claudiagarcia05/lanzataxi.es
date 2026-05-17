<?php

    namespace Database\Seeders;

    use App\Models\User;
    use Illuminate\Database\Console\Seeds\WithoutModelEvents;
    use Illuminate\Database\Seeder;
    use Illuminate\Support\Facades\Hash;

    /**
     * Seeder principal de la base de datos.
     *
     * Se ejecuta con `php artisan db:seed` (y normalmente también tras
     * `php artisan migrate --seed`). Aquí se definen los datos iniciales
     * necesarios para desarrollo/pruebas.
     */
    class DatabaseSeeder extends Seeder {
        use WithoutModelEvents;

        /**
         * Ejecuta los seeders.
         */
        public function run(): void {
            // Crea o actualiza un usuario administrador por defecto.
            // Nota de seguridad: en producción NO se recomienda usar credenciales
            // conocidas; este seeder está orientado a entornos de desarrollo.
            User::updateOrCreate([
                'email' => 'admin@admin.es',
            ], [
                'name' => 'Admin',
                // Contraseña de desarrollo. Cambiarla si se habilita en entornos compartidos.
                'password' => Hash::make('password1234@'),
                'role' => 'admin',
                'phone' => '+34 600 333 333',
                'wallet_balance' => 0,
                'is_disabled' => false,
                'disabled_at' => null,
            ]);
        }
    }