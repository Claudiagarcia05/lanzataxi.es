<?php

    namespace App\Models;

    // use Illuminate\Contracts\Auth\MustVerifyEmail;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Foundation\Auth\User as Authenticatable;
    use Illuminate\Notifications\Notifiable;
    use Laravel\Sanctum\HasApiTokens;

    class User extends Authenticatable {
        /** @use HasFactory<\Database\Factories\UserFactory> */
        use HasApiTokens, HasFactory, Notifiable;

        /**
         * The attributes that are mass assignable.
         *
         * @var list<string>
         */
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

        /**
         * The attributes that should be hidden for serialization.
         *
         * @var list<string>
         */
        protected $hidden = [
            'password',
            'remember_token',
        ];

        /**
         * Get the attributes that should be cast.
         *
         * @return array<string, string>
         */
        protected function casts(): array {

            return [
                'email_verified_at' => 'datetime',
                'password' => 'hashed',
                'wallet_balance' => 'decimal:2',
                'is_disabled' => 'boolean',
                'disabled_at' => 'datetime',
            ];
        }

        public function conductor() {

            return $this->hasOne(Conductor::class);
        }

        public function viajesAspasajero() {

            return $this->hasMany(Viaje::class, 'pasajero_id');
        }

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

        public function rutaFavoritas() {

            return $this->hasMany(RutaFavorita::class);
        }

        public function deudas() {

            return $this->hasMany(Deuda::class);
        }

        public function PerfilPasajero() {

            return $this->hasOne(PerfilPasajero::class);
        }
    }