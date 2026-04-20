<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    /**
     * Modelo RutaFavorita.
     *
     * Guarda ubicaciones frecuentes del usuario (casa, trabajo, etc.) con un
     * orden configurable para el frontend.
     */
    class RutaFavorita extends Model {
        use HasFactory;

        protected $table = 'rutas_favoritas';

        protected $fillable = [
            'user_id',
            'name',
            'address',
            'lat',
            'lng',
            'order'
        ];

        protected $casts = [
            'lat' => 'float',
            'lng' => 'float',
            'order' => 'integer'
        ];

        /**
         * Usuario al que pertenece la ruta favorita.
         */
        public function user() {

            return $this->belongsTo(User::class);
        }
    }