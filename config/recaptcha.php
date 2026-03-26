<?php

    return [
        // Activa/desactiva reCAPTCHA sin tocar código.
        'enabled' => (bool) env('RECAPTCHA_ENABLED', false),

        // Keys (Google reCAPTCHA v3)
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),

        // Reglas v3
        'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
        // Hostname esperado. Puedes usar una lista separada por comas (recomendado para www/no-www).
        // Ej: "lanzataxi.es,www.lanzataxi.es"
        'hostname' => env('RECAPTCHA_HOSTNAME'),
        'hostnames' => array_values(array_filter(array_map('trim', explode(',', (string) env('RECAPTCHA_HOSTNAMES', ''))))),
        'timeout' => (int) env('RECAPTCHA_TIMEOUT', 5),

        // Acciones esperadas (v3)
        'actions' => [
            'login' => env('RECAPTCHA_ACTION_LOGIN', 'login'),
            'register' => env('RECAPTCHA_ACTION_REGISTER', 'register'),
        ],
    ];
