<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Factories\HasFactory;

    class Deuda extends Model {
        use HasFactory;

        protected $table = 'debts';

        protected $fillable = ['user_id', 'trip_id', 'amount', 'status', 'reason'];

        protected $casts = [
            'amount' => 'decimal:2',
        ];

        public function user() {

            return $this->belongsTo(User::class);
        }

        public function trip() {

            return $this->belongsTo(Viaje::class, 'trip_id');
        }
    }