<?php

    namespace Database\Seeders;

    use App\Models\Conductor;
    use App\Models\Ubicacion;
    use App\Models\Pago;
    use App\Models\Taxi;
    use App\Models\Viaje;
    use App\Models\User;
    use Illuminate\Database\Console\Seeds\WithoutModelEvents;
    use Illuminate\Database\Seeder;
    use Illuminate\Support\Facades\Hash;

    class DatabaseSeeder extends Seeder {
        use WithoutModelEvents;

        public function run(): void {
            $pasajero = User::updateOrCreate([
                'email' => 'cliente@test.com',
            ], [
                'name' => 'Carlos Cliente',
                'password' => Hash::make('password'),
                'role' => 'pasajero',
                'phone' => '+34 600 111 111',
            ]);

            $conductorUser = User::updateOrCreate([
                'email' => 'taxista@test.com',
            ], [
                'name' => 'Roberto Taxista',
                'password' => Hash::make('password'),
                'role' => 'conductor',
                'phone' => '+34 600 222 222',
            ]);

            $admin = User::updateOrCreate([
                'email' => 'admin@test.com',
            ], [
                'name' => 'Ana Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+34 600 333 333',
            ]);

            $conductor = Conductor::updateOrCreate([
                'user_id' => $conductorUser->id,
            ], [
                'license_number' => 'LIC-123456',
                'rating' => 4.9,
                'is_active' => true,
            ]);

            $taxi = Taxi::updateOrCreate([
                'plate' => '1234-ABC',
            ], [
                'conductor_id' => $conductor->id,
                'model' => 'Toyota Prius',
                'capacity' => 4,
                'color' => 'Blanco',
                'status' => 'available',
            ]);

            $completedviaje = Viaje::updateOrCreate([
                'pasajero_id' => $pasajero->id,
                'conductor_id' => $conductor->id,
                'taxi_id' => $taxi->id,
                'pickup_address' => 'Arrecife',
                'dropoff_address' => 'Puerto del Carmen',
            ], [
                'pickup_lat' => 28.963,
                'pickup_lng' => -13.550,
                'dropoff_lat' => 28.978,
                'dropoff_lng' => -13.561,
                'status' => 'completed',
                'distance' => 12.5,
                'price' => 18.50,
                'co2_saved' => 0.5,
                'rating' => 5,
                'comment' => 'Excelente servicio',
                'end_time' => now()->subHour(),
            ]);

            Viaje::updateOrCreate([
                'pasajero_id' => $pasajero->id,
                'pickup_address' => 'Teguise',
                'dropoff_address' => 'Aeropuerto César Manrique',
            ], [
                'pickup_lat' => 29.060,
                'pickup_lng' => -13.560,
                'dropoff_lat' => 28.945,
                'dropoff_lng' => -13.605,
                'status' => 'pending',
                'distance' => 15.2,
                'price' => 22.00,
                'co2_saved' => 0.61,
            ]);

            Pago::updateOrCreate([
                'viaje_id' => $completedviaje->id,
            ], [
                'amount' => 18.50,
                'method' => 'card',
                'status' => 'paid',
            ]);

            Ubicacion::updateOrCreate([
                'conductor_id' => $conductor->id,
            ], [
                'lat' => 28.968,
                'lng' => -13.556,
            ]);
        }
    }