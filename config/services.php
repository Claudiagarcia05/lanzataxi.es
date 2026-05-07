<?php

    /**
     * Servicios de terceros.
     *
     * Llaves/tokens se cargan desde `.env` para no versionar secretos.
     */

    return [
        'postmark' => [
            'key' => env('POSTMARK_API_KEY'),
        ],

        'resend' => [
            'key' => env('RESEND_API_KEY'),
        ],

        'ses' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],

        'slack' => [
            'notifications' => [
                'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
                'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
            ],
        ],

        'osrm' => [
            // Base URL del motor de rutas (sin /route/v1).
            // Ejemplo producción: https://router.midominio.com
            'base_url' => env('OSRM_BASE_URL', 'https://router.project-osrm.org'),
        ],
    ];