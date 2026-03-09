<?php

    // Comandos Artisan definidos a nivel de rutas de consola.
    // Aquí se registran comandos simples sin necesidad de clases dedicadas.

    use Illuminate\Foundation\Inspiring;
    use Illuminate\Support\Facades\Artisan;

    // Comando de ejemplo: muestra una frase inspiradora
    Artisan::command('inspire', function () {
        $this->comment(Inspiring::quote());
    })->purpose('Display an inspiring quote');