<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarConductorAprobado
{
    public function handle(Request $solicitud, Closure $next): Response
    {
        $usuario = $solicitud->user();

        if (!$usuario) {
            return $next($solicitud);
        }

        if (($usuario->role ?? null) !== 'conductor') {
            return $next($solicitud);
        }

        $estado = $usuario->conductor?->approval_status;
        if ($estado !== 'approved') {
            if (!$solicitud->is('api/*') && !$solicitud->expectsJson()) {
                Auth::guard('web')->logout();
                $solicitud->session()->invalidate();
                $solicitud->session()->regenerateToken();

                // Mensaje requerido por el flujo de rechazo (también aplica a pendiente).
                return redirect()->route('login')->withErrors([
                    'email' => 'No tienes permiso de taxista',
                ]);
            }

            return response()->json(['message' => 'No tienes permiso de taxista'], 403);
        }

        return $next($solicitud);
    }
}
