<?php

    namespace App\Http\Middleware;

    use Closure;

    class EncabezadosSeguridad {
        public function handle($solicitud, Closure $next) {
            $respuesta = $next($solicitud);

            $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
            $respuesta->headers->set('X-XSS-Protection', '1; mode=block');
            $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

            return $respuesta;
        }
    }