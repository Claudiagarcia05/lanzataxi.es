<?php

    namespace App\Http\Middleware;

    use Closure;
    use Illuminate\Http\Request;
    use Symfony\Component\HttpFoundation\Response;

    /**
     * Middleware de autorización por rol.
     *
     * Uso típico en rutas:
     * - `->middleware('rol:admin')`
     * - `->middleware('rol:admin,conductor')`
     *
     * Respuesta:
     * - API/JSON: 401 (no autenticado) o 403 (sin permisos)
     * - Web: redirección a login o abort(403)
     */
    class MiddlewareRol {
        /**
         * Valida que el usuario esté autenticado y que su rol esté en la lista permitida.
         */
        public function handle(Request $solicitud, Closure $next, ...$roles): Response {
            $usuario = $solicitud->user();

            if (!$usuario) {
                // Diferencia API vs web para una UX correcta.
                if ($solicitud->is('api/*') || $solicitud->expectsJson()) {

                    return response()->json(['message' => 'No autorizado'], 401);
                }

                return redirect()->route('login');
            }

            if (!in_array($usuario->role, $roles, true)) {
                if ($solicitud->is('api/*') || $solicitud->expectsJson()) {
                    
                    return response()->json(['message' => 'No tienes permiso para acceder a esta página.'], 403);
                }

                abort(403, 'No tienes permiso para acceder a esta pagina.');
            }

            return $next($solicitud);
        }
    }