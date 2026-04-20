<?php

    /**
     * Configuración de reCAPTCHA v3.
     *
     * Se usa junto con el servicio `App\Services\RecaptchaV3`.
     * - `enabled`: activa/desactiva la verificación.
     * - `site_key`/`secret_key`: claves de Google.
     * - `min_score`: umbral anti-bot.
     * - `hostname(s)`: lista blanca de dominios permitidos.
     * - `actions`: deben coincidir con la acción enviada desde el frontend.
     */

    return [
        // Si está a false, el backend saltará la verificación.
        'enabled' => (bool) env('RECAPTCHA_ENABLED', false),

        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),

        'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
        // Hostname único opcional.
        'hostname' => env('RECAPTCHA_HOSTNAME'),
        // Lista de hostnames separados por coma (p.ej. "dominio.com, www.dominio.com").
        'hostnames' => array_values(array_filter(array_map('trim', explode(',', (string) env('RECAPTCHA_HOSTNAMES', ''))))),
        'timeout' => (int) env('RECAPTCHA_TIMEOUT', 5),

        'actions' => [
            // Acciones esperadas por el backend (deben cuadrar con el frontend).
            'login' => env('RECAPTCHA_ACTION_LOGIN', 'login'),
            'register' => env('RECAPTCHA_ACTION_REGISTER', 'register'),
        ],
    ];
