<?php

    /*
    |--------------------------------------------------------------------------
    | Console Routes
    |--------------------------------------------------------------------------
    | Definición de comandos Artisan "inline". Normalmente se usa para comandos
    | muy simples o de ejemplo.
    */

    use Illuminate\Foundation\Inspiring;
    use Illuminate\Support\Facades\Artisan;

    Artisan::command('inspire', function () {
        // Imprime una cita inspiradora en consola.
        $this->comment(Inspiring::quote());
    })->purpose('Display an inspiring quote');