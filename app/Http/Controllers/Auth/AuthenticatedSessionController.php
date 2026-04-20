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

    /**
     * Inicio y cierre de sesión (guard web).
     *
     * Nota: este flujo es para la parte web/Inertia. Para API con token Sanctum,
     * ver los controladores en `app/Http/Controllers/Api`.
     */
    class AuthenticatedSessionController extends Controller {
        /**
         * Muestra el formulario de login.
         */
        public function create(): Response {

            return Inertia::render('Auth/Login', [
                'canResetPassword' => Route::has('password.request'),
                'status' => session('status'),
            ]);
        }

        /**
         * Autentica al usuario y regenera la sesión.
         *
         * Controles adicionales de negocio:
         * - Si la cuenta está deshabilitada (`is_disabled`), se cierra sesión.
         * - Si el rol es conductor, exige aprobación (`approval_status = approved`).
         */
        public function store(LoginRequest $solicitud): RedirectResponse {
            $solicitud->authenticate();

            $usuario = $solicitud->user();
            if (!empty($usuario?->is_disabled)) {
            // Evita que una cuenta deshabilitada mantenga sesión web.
                Auth::guard('web')->logout();

                return back()->withErrors([
                    'email' => 'Tu cuenta está desactivada.',
                ]);
            }

            if (($usuario?->role ?? null) === 'conductor') {
                $usuario->loadMissing('conductor');
                if (($usuario->conductor?->approval_status ?? null) !== 'approved') {
                    // Los conductores no aprobados no pueden iniciar sesión.
                    Auth::guard('web')->logout();

                    return back()->withErrors([
                        'email' => 'No tienes permiso de taxista',
                    ]);
                }
            }

            // Mitiga fijación de sesión tras login.
            $solicitud->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        /**
         * Cierra sesión e invalida la sesión actual.
         */
        public function destroy(Request $solicitud): RedirectResponse {
            Auth::guard('web')->logout();

            // Invalida por completo la sesión previa.
            $solicitud->session()->invalidate();

            // Regenera token CSRF.
            $solicitud->session()->regenerateToken();

            return redirect('/');
        }
    }