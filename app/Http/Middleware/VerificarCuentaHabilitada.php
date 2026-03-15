<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarCuentaHabilitada
{
    public function handle(Request $solicitud, Closure $next): Response
    {
        $usuario = $solicitud->user();

        if (!$usuario) {
            return $next($solicitud);
        }

        if (!empty($usuario->is_disabled)) {
            // Si está desactivado, forzamos cierre de sesión en web.
            if (!$solicitud->is('api/*') && !$solicitud->expectsJson()) {
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
