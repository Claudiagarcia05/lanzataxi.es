<?php

    namespace App\Http\Middleware;

    use Closure;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Symfony\Component\HttpFoundation\Response;

    /**
     * Verifica que un usuario con rol `conductor` esté aprobado.
     *
     * - En web: si no está aprobado, cierra sesión y redirige a login.
     * - En API/JSON: devuelve 403.
     */
    class VerificarConductorAprobado {
        /**
         * Aplica la regla de conductor aprobado solo si el usuario está autenticado y es conductor.
         */
        public function handle(Request $solicitud, Closure $next): Response {
            $usuario = $solicitud->user();

            if (!$usuario) {

                return $next($solicitud);
            }

            if (($usuario->role ?? null) !== 'conductor') {

                return $next($solicitud);
            }

            // Si falta la relación por lazy loading, el operador nullsafe evita errores.
            $estado = $usuario->conductor?->approval_status;
            if ($estado !== 'approved') {
                if (!$solicitud->is('api/*') && !$solicitud->expectsJson()) {
                    // En web, se fuerza logout para evitar navegación parcial.
                    Auth::guard('web')->logout();
                    $solicitud->session()->invalidate();
                    $solicitud->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'email' => 'No tienes permiso de taxista',
                    ]);
                }

                return response()->json(['message' => 'No tienes permiso de taxista'], 403);
            }

            return $next($solicitud);
        }
    }