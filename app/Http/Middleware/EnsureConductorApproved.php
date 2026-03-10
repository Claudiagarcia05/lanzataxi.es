<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureConductorApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (($user->role ?? null) !== 'conductor') {
            return $next($request);
        }

        $status = $user->conductor?->approval_status;
        if ($status !== 'approved') {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Mensaje requerido por el flujo de rechazo (también aplica a pendiente).
                return redirect()->route('login')->withErrors([
                    'email' => 'No tienes permiso de taxista',
                ]);
            }

            return response()->json(['message' => 'No tienes permiso de taxista'], 403);
        }

        return $next($request);
    }
}
