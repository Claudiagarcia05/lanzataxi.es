<?php

    namespace App\Providers;

    use Illuminate\Support\Facades\Vite;
    use Illuminate\Support\ServiceProvider;

    /**
     * Service Provider principal de la aplicación.
     *
     * Aquí se registran bindings en el contenedor (register) y se ejecuta la
     * configuración/bootstrapping de servicios (boot).
     */
    class AppServiceProvider extends ServiceProvider {
        /**
         * Registro de servicios/bindings.
         *
         * Se ejecuta antes de `boot()`.
         */
        public function register(): void {

        }

        /**
         * Inicialización/configuración tras registrar todos los providers.
         */
        public function boot(): void {
            // Prefetch de assets Vite para mejorar percepción de rendimiento.
            // `concurrency` limita cuántas peticiones simultáneas se realizan.
            Vite::prefetch(concurrency: 3);
        }
    }