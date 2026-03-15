<?php

    namespace App\Http\Middleware;

    use Closure;
    use Illuminate\Http\Request;
    use Symfony\Component\HttpFoundation\Response;

    class MiddlewareRol {
        /**
         * Handle an incoming request.
         *
         * @param  array<int, string>  $roles
         */
        public function handle(Request $solicitud, Closure $next, ...$roles): Response {
            $usuario = $solicitud->user();

            if (!$usuario) {
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