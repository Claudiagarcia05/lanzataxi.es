<?php

    namespace Database\Seeders;

    use App\Models\User;
    use Illuminate\Database\Console\Seeds\WithoutModelEvents;
    use Illuminate\Database\Seeder;
    use Illuminate\Support\Facades\Hash;

    class DatabaseSeeder extends Seeder {
        use WithoutModelEvents;

        public function run(): void {
            // Estado inicial para pruebas: solo existe el admin base.
            // Importante: cambia esta contraseña tras el primer acceso.
            User::updateOrCreate([
                'email' => 'admin@admin.es',
            ], [
                'name' => 'Ana Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+34 600 333 333',
                'wallet_balance' => 0,
                'is_disabled' => false,
                'disabled_at' => null,
            ]);
        }
    }