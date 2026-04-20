<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Foundation\Auth\User as Authenticatable;
    use Illuminate\Notifications\Notifiable;
    use Laravel\Sanctum\HasApiTokens;

    /**
     * Modelo User.
     *
     * Usuario del sistema (pasajero / conductor / admin).
     * Incluye saldo de cartera (`wallet_balance`) y estado de cuenta (`is_disabled`).
     */
    class User extends Authenticatable {
        use HasApiTokens, HasFactory, Notifiable;

        protected $fillable = [
            'name',
            'email',
            'password',
            'role',
            'phone',
            'is_disabled',
            'disabled_at',
            'avatar',
            'wallet_balance',
        ];

        protected $hidden = [
            'password',
            'remember_token',
        ];

        protected function casts(): array {

            return [
                'email_verified_at' => 'datetime',
                'password' => 'hashed',
                'wallet_balance' => 'decimal:2',
                'is_disabled' => 'boolean',
                'disabled_at' => 'datetime',
            ];
        }

        /**
         * Perfil de conductor (si el usuario tiene rol `conductor`).
         */
        public function conductor() {

            return $this->hasOne(Conductor::class);
        }

        /**
         * Viajes donde el usuario actúa como pasajero.
         */
        public function viajesAspasajero() {

            return $this->hasMany(Viaje::class, 'pasajero_id');
        }

        /**
         * Viajes aceptados/realizados a través del perfil de conductor.
         *
         * Nota: el nombre del método es legado y no sigue el estilo habitual.
         */
        public function acceptedviajesAsconductor() {

            return $this->hasManyThrough(
                Viaje::class,
                Conductor::class,
                'user_id',
                'conductor_id',
                'id',
                'id'
            );
        }

        /**
         * Rutas favoritas del usuario.
         */
        public function rutaFavoritas() {

            return $this->hasMany(RutaFavorita::class);
        }

        /**
         * Deudas asociadas al usuario.
         */
        public function deudas() {

            return $this->hasMany(Deuda::class);
        }

        /**
         * Perfil extendido de pasajero.
         *
         * Nota: el nombre del método empieza en mayúscula por compatibilidad.
         */
        public function PerfilPasajero() {

            return $this->hasOne(PerfilPasajero::class);
        }
    }