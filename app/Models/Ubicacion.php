<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    /**
     * Modelo Ubicacion.
     *
     * Guarda una muestra de ubicación (lat/lng) del conductor.
     * Normalmente se inserta como histórico (una fila por actualización).
     */
    class Ubicacion extends Model {
        protected $fillable = ['conductor_id', 'lat', 'lng'];

        /**
         * Conductor al que pertenece esta ubicación.
         */
        public function conductor() {
            
            return $this->belongsTo(Conductor::class);
        }
    }