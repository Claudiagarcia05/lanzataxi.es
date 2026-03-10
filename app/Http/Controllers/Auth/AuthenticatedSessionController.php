<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Auth\LoginRequest;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;
    use Inertia\Inertia;
    use Inertia\Response;

    class AuthenticatedSessionController extends Controller {
        public function create(): Response {

            return Inertia::render('Auth/Login', [
                'canResetPassword' => Route::has('password.request'),
                'status' => session('status'),
            ]);
        }

        public function store(LoginRequest $solicitud): RedirectResponse {
            $solicitud->authenticate();

            $usuario = $solicitud->user();
            if (!empty($usuario?->is_disabled)) {
                Auth::guard('web')->logout();

                return back()->withErrors([
                    'email' => 'Tu cuenta está desactivada.',
                ]);
            }

            if (($usuario?->role ?? null) === 'conductor') {
                $usuario->loadMissing('conductor');
                if (($usuario->conductor?->approval_status ?? null) !== 'approved') {
                    Auth::guard('web')->logout();

                    return back()->withErrors([
                        'email' => 'No tienes permiso de taxista',
                    ]);
                }
            }

            $solicitud->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        public function destroy(Request $solicitud): RedirectResponse {
            Auth::guard('web')->logout();

            $solicitud->session()->invalidate();

            $solicitud->session()->regenerateToken();

            return redirect('/');
        }
    }