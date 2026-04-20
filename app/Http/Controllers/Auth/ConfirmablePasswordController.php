<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Validation\ValidationException;
    use Inertia\Inertia;
    use Inertia\Response;

    /**
     * Confirmación de contraseña para acciones sensibles.
     *
     * Laravel guarda un timestamp en sesión (`auth.password_confirmed_at`) y
     * permite acceder a rutas protegidas por el middleware correspondiente.
     */
    class ConfirmablePasswordController extends Controller {
        /**
         * Muestra la pantalla de confirmación de contraseña.
         */
        public function show(): Response {

            return Inertia::render('Auth/ConfirmPassword');
        }

        /**
         * Valida la contraseña del usuario actual y marca la confirmación en sesión.
         */
        public function store(Request $solicitud): RedirectResponse {
            if (! Auth::guard('web')->validate([
                'email' => $solicitud->user()->email,
                'password' => $solicitud->password,
            ])) {
                throw ValidationException::withMessages([
                    'password' => __('auth.password'),
                ]);
            }

            // Timestamp (segundos) usado por middleware de confirmación de contraseña.
            $solicitud->session()->put('auth.password_confirmed_at', time());

            return redirect()->intended(route('dashboard', absolute: false));
        }
    }