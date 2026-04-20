<?php

    namespace App\Http\Middleware;

    use Illuminate\Http\Request;
    use Inertia\Middleware;

    /**
     * Middleware de Inertia.
     *
     * Define la vista root y los “props compartidos” que viajan a todas las
     * páginas Inertia (locale, usuario autenticado, etc.).
     */
    class HandleInertiaRequests extends Middleware {
        /**
         * Vista base de Inertia.
         */
        protected $rootView = 'app';

        /**
         * Permite versionado para invalidar assets cuando cambian.
         */
        public function version(Request $solicitud): ?string {

            return parent::version($solicitud);
        }

        /**
         * Props compartidos globalmente en el frontend.
         */
        public function share(Request $solicitud): array {

            return [
                ...parent::share($solicitud),
                // Idioma actual del backend.
                'locale' => app()->getLocale(),
                // Para que el frontend muestre selector/lista.
                'localesDisponibles' => ['es', 'en'],
                'auth' => [
                    // Usuario autenticado (o null).
                    'user' => $solicitud->user(),
                ],
            ];
        }
    }