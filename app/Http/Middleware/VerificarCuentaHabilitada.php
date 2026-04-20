<?php

    namespace App\Http\Middleware;

    use Closure;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Symfony\Component\HttpFoundation\Response;

    /**
     * Verifica que la cuenta del usuario no esté deshabilitada.
     *
     * Reacciona distinto según el tipo de request:
     * - Web: logout + invalidate session + redirect con mensaje.
     * - API/JSON: 403 con mensaje.
     */
    class VerificarCuentaHabilitada {
        /**
         * Bloquea el acceso cuando `is_disabled` es true.
         */
        public function handle(Request $solicitud, Closure $next): Response {
            $usuario = $solicitud->user();

            if (!$usuario) {

                return $next($solicitud);
            }

            if (!empty($usuario->is_disabled)) {
                if (!$solicitud->is('api/*') && !$solicitud->expectsJson()) {
                    // En web: se corta navegación cerrando sesión.
                    Auth::guard('web')->logout();
                    $solicitud->session()->invalidate();
                    $solicitud->session()->regenerateToken();

                    return redirect('/')->with('error', 'Tu cuenta está desactivada.');
                }

                return response()->json(['message' => 'Tu cuenta está desactivada.'], 403);
            }

            return $next($solicitud);
        }
    }