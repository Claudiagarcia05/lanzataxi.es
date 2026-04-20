<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    /**
     * Modelo Taxi.
     *
     * Representa el vehículo asociado a un conductor.
     * Campos como `status` se usan para disponibilidad (available/busy/offline).
     */
    class Taxi extends Model {
        use HasFactory;

        protected $fillable = ['conductor_id', 'plate', 'model', 'capacity', 'color', 'status'];

        /**
         * Conductor dueño del taxi.
         */
        public function conductor() {

            return $this->belongsTo(Conductor::class);
        }

        /**
         * Viajes realizados con este taxi.
         */
        public function viajes() {

            return $this->hasMany(Viaje::class);
        }
    }