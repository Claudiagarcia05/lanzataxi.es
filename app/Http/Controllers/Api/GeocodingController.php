<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingController
{
    private const NOMINATIM_BASE_URL = 'https://nominatim.openstreetmap.org';

    // Límites aproximados para Lanzarote (mismo rango que usa el frontend).
    private const LANZAROTE_SOUTH = 28.85;
    private const LANZAROTE_WEST = -13.95;
    private const LANZAROTE_NORTH = 29.35;
    private const LANZAROTE_EAST = -13.20;

    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'addressdetails' => ['nullable'],
        ]);

        $query = trim($validated['q']);
        $limit = (int) ($validated['limit'] ?? 5);
        $addressDetails = $request->boolean('addressdetails', false);

        // Forzamos parámetros seguros para evitar abuso del proxy.
        // - countrycodes=es + bounded=1 + viewbox de Lanzarote: reduce resultados y carga.
        // - format=json: el frontend espera JSON.
        $params = [
            'q' => $query,
            'format' => 'json',
            'limit' => $limit,
            'countrycodes' => 'es',
            'bounded' => 1,
            'viewbox' => sprintf(
                '%s,%s,%s,%s',
                self::LANZAROTE_WEST,
                self::LANZAROTE_NORTH,
                self::LANZAROTE_EAST,
                self::LANZAROTE_SOUTH
            ),
        ];

        if ($addressDetails) {
            $params['addressdetails'] = 1;
        }

        $cacheKey = 'geocoding:nominatim:search:' . sha1(json_encode($params));

        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($params) {
            $response = $this->nominatimClient()
                ->get(self::NOMINATIM_BASE_URL . '/search', $params);

            if (!$response->successful()) {
                return [
                    '__proxy_error' => true,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ];
            }

            return $response->json();
        });

        if (is_array($data) && ($data['__proxy_error'] ?? false)) {
            return response()->json([
                'message' => 'No se pudo geocodificar la dirección en este momento.',
            ], 502);
        }

        return response()->json($data);
    }

    public function reverse(Request $request)
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric'],
            'lon' => ['required', 'numeric'],
        ]);

        $lat = (float) $validated['lat'];
        $lon = (float) $validated['lon'];

        // Validación defensiva en backend: evita usar el proxy para coordenadas fuera de Lanzarote.
        if (!$this->estaEnLanzarote($lat, $lon)) {
            return response()->json([
                'message' => 'Solo se admiten coordenadas dentro de Lanzarote.',
            ], 422);
        }

        $params = [
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'json',
        ];

        $cacheKey = 'geocoding:nominatim:reverse:' . sha1(json_encode($params));

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($params) {
            $response = $this->nominatimClient()
                ->get(self::NOMINATIM_BASE_URL . '/reverse', $params);

            if (!$response->successful()) {
                return [
                    '__proxy_error' => true,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ];
            }

            return $response->json();
        });

        if (is_array($data) && ($data['__proxy_error'] ?? false)) {
            return response()->json([
                'message' => 'No se pudo obtener la dirección para estas coordenadas.',
            ], 502);
        }

        return response()->json($data);
    }

    private function estaEnLanzarote(float $lat, float $lon): bool
    {
        return $lat >= self::LANZAROTE_SOUTH
            && $lat <= self::LANZAROTE_NORTH
            && $lon >= self::LANZAROTE_WEST
            && $lon <= self::LANZAROTE_EAST;
    }

    private function nominatimClient()
    {
        // Nominatim recomienda enviar un User-Agent identificable.
        $appName = (string) config('app.name', 'LanzaTaxi');
        $appUrl = (string) config('app.url', '');
        $userAgent = $appUrl ? sprintf('%s (%s)', $appName, $appUrl) : $appName;

        return Http::acceptJson()
            ->timeout(10)
            ->connectTimeout(5)
            ->withHeaders([
                'User-Agent' => $userAgent,
                'Referer' => $appUrl,
            ]);
    }
}
