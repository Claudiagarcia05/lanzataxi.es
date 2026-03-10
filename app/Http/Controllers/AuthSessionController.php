<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Laravel\Sanctum\PersonalAccessToken;

    class AuthSessionController extends Controller {
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
                $usuario->loadMissing('conductor');
                if (($usuario->conductor?->approval_status ?? null) !== 'approved') {
                    return redirect()->route('login')->withErrors([
                        'email' => 'No tienes permiso de taxista',
                    ]);
                }
            }

            Auth::guard('web')->login($usuario, remember: true);
            
            \Log::info('User authenticated via API token', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
            ]);
            
            return redirect(match ($usuario->role) {
                'conductor' => '/conductor/dashboard',
                'admin' => '/admin/dashboard',
                default => '/pasajero/home',
            });
        }
    }