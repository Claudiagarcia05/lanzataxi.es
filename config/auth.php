<?php

    /**
     * Configuración de autenticación.
     *
     * Define guards (cómo se autentica) y providers (de dónde salen los usuarios).
     * En esta app se usa el guard `web` basado en sesión.
     */

    return [
        'defaults' => [
            // Guard por defecto cuando no se especifica uno explícitamente.
            'guard' => env('AUTH_GUARD', 'web'),
            'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
        ],

        'guards' => [
            'web' => [
                // Sesiones/cookies para navegación web (Inertia/Blade).
                'driver' => 'session',
                'provider' => 'users',
            ],
        ],

        'providers' => [
            'users' => [
                'driver' => 'eloquent',
                // Modelo Eloquent que representa al usuario autenticable.
                'model' => env('AUTH_MODEL', App\Models\User::class),
            ],
        ],

        'passwords' => [
            'users' => [
                'provider' => 'users',
                'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
                // Expiración del token de reset en minutos.
                'expire' => 60,
                // Throttle en minutos para evitar abusos del reset.
                'throttle' => 60,
            ],
        ],

        // Tiempo (en segundos) durante el cual se confirmará la contraseña en acciones sensibles.
        'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
    ];