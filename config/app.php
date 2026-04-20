<?php

    /**
     * Configuración base de la aplicación.
     *
     * Estas opciones se alimentan principalmente desde variables de entorno
     * (`.env`) y afectan a comportamiento global (entorno, URL, idioma, clave).
     */

    return [
        // Nombre visible de la aplicación.
        'name' => env('APP_NAME', 'Laravel'),

        // Entorno actual: local, staging, production, etc.
        'env' => env('APP_ENV', 'production'),

        // En producción debe ser false para no exponer trazas o detalles sensibles.
        'debug' => (bool) env('APP_DEBUG', false),

        // URL base usada para generar enlaces.
        'url' => env('APP_URL', 'http://localhost'),

        // Zona horaria por defecto para fechas/horas.
        'timezone' => 'UTC',

        // Idioma por defecto y de fallback (si no existe traducción).
        'locale' => env('APP_LOCALE', 'en'),

        'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

        'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

        // Cifrado base utilizado por Laravel.
        'cipher' => 'AES-256-CBC',

        // Clave de la app. Imprescindible en producción.
        // Cambiarla invalida cookies/sesiones cifradas y otros datos protegidos.
        'key' => env('APP_KEY'),

        // Claves anteriores para permitir rotación gradual (p.ej. despliegues).
        'previous_keys' => [
            ...array_filter(
                explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
            ),
        ],

        'maintenance' => [
            // Modo mantenimiento: driver y store.
            'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
            'store' => env('APP_MAINTENANCE_STORE', 'database'),
        ],
    ];