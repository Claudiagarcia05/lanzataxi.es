<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    /**
     * Modelo Pago.
     *
     * Registra el pago asociado a un viaje (método, estado y referencia).
     */
    class Pago extends Model {
        protected $fillable = [
            'viaje_id',
            'amount',
            'method',
            'status',
            'transaction_id',
        ];

        /**
         * Viaje al que pertenece este pago.
         */
        public function viaje() {
            
            return $this->belongsTo(Viaje::class);
        }
    }