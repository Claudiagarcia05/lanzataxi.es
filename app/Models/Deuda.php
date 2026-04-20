<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * Modelo Deuda.
     *
     * Representa una deuda pendiente/pagada de un usuario, normalmente asociada
     * a un viaje (p.ej. cancelación cobrable sin saldo suficiente).
     */
    class Deuda extends Model {
        use HasFactory;

        protected $table = 'debts';

        protected $fillable = ['user_id', 'trip_id', 'amount', 'status', 'reason'];

        protected $casts = [
            'amount' => 'decimal:2',
        ];

        /**
         * Usuario deudor.
         */
        public function user() {

            return $this->belongsTo(User::class);
        }

        /**
         * Viaje asociado a la deuda (si aplica).
         */
        public function trip() {

            return $this->belongsTo(Viaje::class, 'trip_id');
        }
    }