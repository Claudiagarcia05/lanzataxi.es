<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Laravel\Sanctum\PersonalAccessToken;

    /**
     * Establece una sesión web (guard `web`) a partir de un token de API (Sanctum).
     *
     * Caso de uso típico: la app cliente obtiene un token y redirige al navegador
     * a este endpoint para “transferir” la autenticación a una sesión web.
     *
     * Seguridad:
     * - El token viaja por query string (`?token=...`): evitar exponerlo en logs,
     *   historial del navegador o referers. Si se puede, preferir POST o cookie.
     * - Este método valida que exista el token, que sea válido y que el usuario
     *   tenga permisos de negocio (cuenta habilitada / conductor aprobado).
     */
    class AuthSessionController extends Controller {
        /**
         * Crea sesión web usando `PersonalAccessToken`.
         *
         * Redirige a un dashboard distinto según el rol.
         */
        public function establishSession(Request $solicitud) {
            $token = $solicitud->query('token');
            
            if (!$token) {

                return redirect('/')->with('error', 'Token requerido');
            }

            $personalAccessToken = PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {

                return redirect('/')->with('error', 'Token inválido');
            }

            $usuario = $personalAccessToken->tokenable;
            
            if (!$usuario) {

                return redirect('/')->with('error', 'Usuario no encontrado');
            }

            if (!empty($usuario->is_disabled)) {

                return redirect('/')->with('error', 'Tu cuenta está desactivada.');
            }

            if (($usuario->role ?? null) === 'conductor') {
                // Los conductores requieren aprobación explícita para operar.
                $usuario->loadMissing('conductor');
                if (($usuario->conductor?->approval_status ?? null) !== 'approved') {
                    
                    return redirect()->route('login')->withErrors([
                        'email' => 'No tienes permiso de taxista',
                    ]);
                }
            }

            // Inicia sesión persistente (remember = true).
            Auth::guard('web')->login($usuario, remember: true);
            
            // Log informativo: no incluye el token por seguridad.
            \Log::info('User authenticated via API token', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
            ]);
            
            // Redirección por rol a su “home” web.
            return redirect(match ($usuario->role) {
                'conductor' => '/conductor/dashboard',
                'admin' => '/admin/dashboard',
                default => '/pasajero/home',
            });
        }
    }