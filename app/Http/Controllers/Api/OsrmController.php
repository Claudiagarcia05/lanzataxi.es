<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OsrmController
{
    private const DEFAULT_BASE_URL = 'https://router.project-osrm.org';

    public function route(Request $request, string $profile, string $coordinates)
    {
        if (!in_array($profile, ['car', 'driving', 'bike', 'foot'], true)) {
            return response()->json([
                'message' => 'Perfil de ruta no permitido.',
            ], 422);
        }

        $points = array_filter(explode(';', $coordinates));
        if (count($points) < 2 || count($points) > 10) {
            return response()->json([
                'message' => 'Número de coordenadas inválido para calcular ruta.',
            ], 422);
        }

        $allowedParams = [
            'alternatives',
            'steps',
            'annotations',
            'geometries',
            'overview',
            'continue_straight',
            'hints',
            'bearings',
            'radiuses',
            'approaches',
            'exclude',
        ];

        $params = [];
        foreach ($allowedParams as $key) {
            if ($request->query($key) !== null) {
                $params[$key] = (string) $request->query($key);
            }
        }

        $baseUrl = $this->normalizeBaseUrl((string) config('services.osrm.base_url', self::DEFAULT_BASE_URL));
        $endpoint = sprintf('%s/route/v1/%s/%s', $baseUrl, $profile, $coordinates);

        $cacheKey = 'osrm:route:' . sha1(json_encode([$endpoint, $params]));

        $data = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($endpoint, $params) {
            $response = $this->osrmClient()->get($endpoint, $params);

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
                'message' => 'No se pudo calcular la ruta en este momento.',
            ], 502);
        }

        return response()->json($data);
    }

    private function osrmClient()
    {
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

    private function normalizeBaseUrl(string $value): string
    {
        $trimmed = rtrim(trim($value), '/');

        if ($trimmed === '') {
            return self::DEFAULT_BASE_URL;
        }

        if (str_ends_with($trimmed, '/route/v1')) {
            return substr($trimmed, 0, -9);
        }

        return $trimmed;
    }
}
