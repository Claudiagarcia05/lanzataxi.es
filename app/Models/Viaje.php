<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    /**
     * Modelo Viaje.
     *
     * Representa una solicitud/servicio de taxi:
     * - Coordenadas de recogida y destino
     * - Estado (`pending`, `accepted`, `in_progress`, `completed`, `cancelled`)
     * - Precio, distancia y “CO2 ahorrado” estimado
     * - Relaciones con pasajero, conductor, taxi y pago
     */
    class Viaje extends Model {
        use HasFactory;

        protected $fillable = [
            'pasajero_id', 'conductor_id', 'taxi_id',
            'pickup_lat', 'pickup_lng', 'dropoff_lat', 'dropoff_lng',
            'pickup_address', 'dropoff_address',
            'status', 'distance', 'price', 'co2_saved',
            'rating', 'comment', 'end_time',
            'pasajeros', 'luggage', 'pago_method', 'notes', 'scheduled_for',
        ];

        protected $casts = [
            'pickup_lat' => 'float',
            'pickup_lng' => 'float',
            'dropoff_lat' => 'float',
            'dropoff_lng' => 'float',
            'distance' => 'float',
            'price' => 'float',
            'co2_saved' => 'float',
            'rating' => 'integer',
            'end_time' => 'datetime',
        ];

        /**
         * Usuario pasajero.
         */
        public function pasajero() {

            return $this->belongsTo(User::class, 'pasajero_id');
        }

        /**
         * Conductor asignado.
         */
        public function conductor() {

            return $this->belongsTo(Conductor::class);
        }

        /**
         * Taxi asignado.
         */
        public function taxi() {

            return $this->belongsTo(Taxi::class);
        }

        /**
         * Pago asociado (si existe).
         */
        public function pago() {

            return $this->hasOne(Pago::class);
        }

        /**
         * Estima el CO2 ahorrado.
         *
         * Implementación: diferencia entre una tasa “coche” y una tasa “taxi”.
         * Los coeficientes son aproximados y se aplican por km.
         */
        public function calculateCO2Saved() {
            if ($this->distance) {
                // kg CO2/km aproximado (valores orientativos).
                $co2_coche = $this->distance * 0.120;
                $co2_taxi = $this->distance * 0.080;

                return round($co2_coche - $co2_taxi, 2);
            }

            return 0;
        }
    }