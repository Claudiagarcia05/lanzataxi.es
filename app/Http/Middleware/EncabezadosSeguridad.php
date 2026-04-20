<?php

    namespace App\Http\Middleware;

    use Closure;

    /**
     * Añade cabeceras HTTP básicas de seguridad a las respuestas.
     *
     * Importante:
     * - Este middleware no sustituye a una Content-Security-Policy (CSP).
     * - En apps modernas, `X-XSS-Protection` puede no tener efecto en navegadores
     *   actuales, pero se mantiene por compatibilidad.
     */
    class EncabezadosSeguridad {
        /**
         * Inyecta cabeceras tras ejecutar el request.
         */
        public function handle($solicitud, Closure $next) {
            $respuesta = $next($solicitud);

            // Evita que la web sea embebida en iframes de otros orígenes.
            $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
            // Evita MIME sniffing.
            $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
            // Cabecera histórica para filtro XSS (compatibilidad).
            $respuesta->headers->set('X-XSS-Protection', '1; mode=block');
            // Reduce información que se envía en el Referer.
            $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

            return $respuesta;
        }
    }