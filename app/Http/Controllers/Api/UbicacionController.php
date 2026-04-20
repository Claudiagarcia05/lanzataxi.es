<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Ubicacion;
    use Illuminate\Http\Request;

    /**
     * Actualización de ubicación del conductor.
     */
    class UbicacionController extends Controller {
        /**
         * Guarda una nueva ubicación (lat/lng) asociada al conductor autenticado.
         */
        public function update(Request $solicitud) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor) {

                return response()->json(['message' => 'conductor profile not found'], 404);
            }

            $validado = $solicitud->validate([
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
            ]);

            // Se persiste como histórico (una fila por actualización).
            $ubicacion = Ubicacion::create([
                'conductor_id' => $conductor->id,
                'lat' => $validado['lat'],
                'lng' => $validado['lng'],
            ]);

            return response()->json($ubicacion, 201);
        }
    }