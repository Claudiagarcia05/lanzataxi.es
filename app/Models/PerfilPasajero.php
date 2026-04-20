<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    /**
     * Modelo PerfilPasajero.
     *
     * Datos adicionales del usuario cuando actúa como pasajero.
     * Se mantiene separado de `users` para no sobrecargar el modelo principal.
     */
    class PerfilPasajero extends Model {
        protected $table = 'perfiles_pasajero';

        protected $fillable = [
            'user_id',
            'avatar',
            'phone_alternative',
            'preferences',
            'total_viajes',
            'total_spent'
        ];

        protected $casts = [
            'preferences' => 'array',
            'total_viajes' => 'integer',
            'total_spent' => 'decimal:2'
        ];

        /**
         * Usuario propietario del perfil.
         */
        public function user() {

            return $this->belongsTo(User::class);
        }
    }