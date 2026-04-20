<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use App\Models\Taxi;

    /**
     * Modelo Conductor.
     *
     * Representa al taxista (perfil asociado a un `User`).
     * Incluye campos de estado (activo/aprobación) y métricas de tiempo conectado.
     */
    class Conductor extends Model {
        use HasFactory;

        protected $fillable = [
            'user_id',
            'license_number',
            'rating',
            'is_active',
            'approval_status',
            'approved_at',
            'rejected_at',
            'online_seconds',
            'online_since',
            'online_seconds_month',
            'online_month',
        ];

        protected $casts = [
            'rating' => 'float',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'online_seconds' => 'integer',
            'online_since' => 'datetime',
            'online_seconds_month' => 'integer',
        ];

        /**
         * Usuario propietario del perfil de conductor.
         */
        public function user() {

            return $this->belongsTo(User::class);
        }

        /**
         * Taxi asociado al conductor.
         */
        public function taxi() {

            return $this->hasOne(Taxi::class);
        }

        /**
         * Asegura que el conductor tenga un taxi asociado.
         *
         * Caso de uso: cuando se necesita mostrar/usar un taxi aunque el registro
         * todavía no exista (p.ej. conductor recién creado).
         *
         * Nota: la matrícula generada es un placeholder y NO es una matrícula real.
         */
        public function ensureTaxiExists(string $statusIfCreate = 'available'): Taxi
        {
            if ($this->relationLoaded('taxi') && $this->taxi) {

                return $this->taxi;
            }

            $existingTaxi = $this->taxi()->first();
            if ($existingTaxi) {
                $this->setRelation('taxi', $existingTaxi);

                return $existingTaxi;
            }

            // Genera una matrícula placeholder única.
            $plateBase = 'PENDIENTE-' . $this->id . '-' . strtoupper(bin2hex(random_bytes(3)));
            $plate = $plateBase;
            $suffix = 0;
            while (Taxi::where('plate', $plate)->exists()) {
                $suffix++;
                $plate = $plateBase . '-' . $suffix;
            }

            $createdTaxi = $this->taxi()->create([
                'plate' => $plate,
                'model' => '',
                'capacity' => 4,
                'color' => null,
                'status' => $statusIfCreate,
            ]);

            $this->setRelation('taxi', $createdTaxi);
            
            return $createdTaxi;
        }

        /**
         * Viajes asignados a este conductor.
         */
        public function viajes() {

            return $this->hasMany(Viaje::class);
        }

        /**
         * Historial de ubicaciones del conductor.
         */
        public function ubicacions() {

            return $this->hasMany(Ubicacion::class);
        }
    }