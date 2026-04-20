<?php

    /**
     * Configuración de sesiones.
     *
     * Recomendación:
     * - En producción, usar `secure`, `http_only` y `same_site` adecuados.
     * - Si usas SPA con subdominios, revisa `domain`/`same_site`.
     */

    use Illuminate\Support\Str;

    return [
        // Driver de sesión (database, file, redis, cookie...).
        'driver' => env('SESSION_DRIVER', 'database'),

        // Duración de la sesión (minutos).
        'lifetime' => (int) env('SESSION_LIFETIME', 120),

        // Si true, la sesión se expira al cerrar el navegador.
        'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

        // Si true, cifra el contenido de la sesión en el almacenamiento.
        'encrypt' => env('SESSION_ENCRYPT', false),

        'files' => storage_path('framework/sessions'),

        'connection' => env('SESSION_CONNECTION'),

        'table' => env('SESSION_TABLE', 'sessions'),

        'store' => env('SESSION_STORE'),

        'lottery' => [2, 100],

        'cookie' => env(
            'SESSION_COOKIE',
            Str::slug((string) env('APP_NAME', 'laravel')).'-session'
        ),

        'path' => env('SESSION_PATH', '/'),

        'domain' => env('SESSION_DOMAIN'),

        // Si true, la cookie sólo se enviará por HTTPS.
        'secure' => env('SESSION_SECURE_COOKIE'),

        // Si true, evita acceso a la cookie desde JavaScript (mitiga XSS).
        'http_only' => env('SESSION_HTTP_ONLY', true),

        // `lax` suele ser un buen equilibrio; `none` requiere `secure=true`.
        'same_site' => env('SESSION_SAME_SITE', 'lax'),

        // Cookies particionadas (soporte depende del navegador).
        'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),
    ];