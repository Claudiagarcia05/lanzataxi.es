<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!empty($user->is_disabled)) {
            // Si está desactivado, forzamos cierre de sesión en web.
            if (!$request->is('api/*') && !$request->expectsJson()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/')->with('error', 'Tu cuenta está desactivada.');
            }

            return response()->json(['message' => 'Tu cuenta está desactivada.'], 403);
        }

        return $next($request);
    }
}
