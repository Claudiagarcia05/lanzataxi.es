<?php

    /**
     * Configuración de Laravel Sanctum.
     *
     * Sanctum soporta autenticación para SPAs (cookies/sesión) y tokens.
     * La lista `stateful` es crítica para SPAs: dominios permitidos a usar
     * cookies de sesión como autenticación.
     */

    use Laravel\Sanctum\Sanctum;

    return [
        'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
            '%s%s',
            'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
            Sanctum::currentApplicationUrlWithPort(),
        ))),

        // Guard(s) que Sanctum utilizará para autenticar peticiones stateful.
        'guard' => ['web'],

        // Expiración de tokens (minutos). Null = no expira automáticamente.
        'expiration' => null,

        // Prefijo opcional para tokens (útil en entornos compartidos).
        'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

        'middleware' => [
            // Middleware usados por Sanctum para flujos stateful.
            'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
            'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
            'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ],
    ];