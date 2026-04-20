<?php

    /**
     * Configuración de almacenamiento (discos).
     *
     * Define dónde se guardan ficheros privados/públicos y cómo se exponen.
     * `public` suele estar enlazado a `storage/app/public` mediante `storage:link`.
     */

    return [
        // Disco por defecto.
        'default' => env('FILESYSTEM_DISK', 'local'),

        'disks' => [
            'local' => [
                'driver' => 'local',
                // Almacenamiento privado (no expuesto directamente por web).
                'root' => storage_path('app/private'),
                'serve' => true,
                'throw' => false,
                'report' => false,
            ],

            'public' => [
                'driver' => 'local',
                // Almacenamiento público (accesible vía /storage).
                'root' => storage_path('app/public'),
                'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],

            's3' => [
                'driver' => 's3',
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION'),
                'bucket' => env('AWS_BUCKET'),
                'url' => env('AWS_URL'),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
                'throw' => false,
                'report' => false,
            ],
        ],

        'links' => [
            // Enlace simbólico público: /public/storage -> /storage/app/public
            public_path('storage') => storage_path('app/public'),
        ],
    ];