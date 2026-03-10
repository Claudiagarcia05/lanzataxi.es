<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use App\Models\Taxi;

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

        public function user() {

            return $this->belongsTo(User::class);
        }

        public function taxi() {

            return $this->hasOne(Taxi::class);
        }

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

            $plateBase = 'TMP-' . $this->id . '-' . strtoupper(bin2hex(random_bytes(3)));
            $plate = $plateBase;
            $suffix = 0;
            while (Taxi::where('plate', $plate)->exists()) {
                $suffix++;
                $plate = $plateBase . '-' . $suffix;
            }

            $createdTaxi = $this->taxi()->create([
                'plate' => $plate,
                'model' => 'Pendiente',
                'capacity' => 4,
                'color' => null,
                'status' => $statusIfCreate,
            ]);

            $this->setRelation('taxi', $createdTaxi);
            return $createdTaxi;
        }

        public function viajes() {

            return $this->hasMany(Viaje::class);
        }

        public function ubicacions() {

            return $this->hasMany(ubicacion::class);
        }
    }