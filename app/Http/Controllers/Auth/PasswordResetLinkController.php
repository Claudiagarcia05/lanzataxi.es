<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Password;
    use Illuminate\Validation\ValidationException;
    use Inertia\Inertia;
    use Inertia\Response;

    /**
     * Solicitud de enlace de restablecimiento de contraseña.
     */
    class PasswordResetLinkController extends Controller {
        /**
         * Muestra el formulario “Olvidé mi contraseña”.
         */
        public function create(): Response {

            return Inertia::render('Auth/ForgotPassword', [
                'status' => session('status'),
            ]);
        }

        /**
         * Envía el enlace de reset al email si existe.
         *
         * Laravel devuelve un estado (enviado / error); se traduce a mensaje.
         */
        public function store(Request $solicitud): RedirectResponse {
            $solicitud->validate([
                'email' => 'required|email',
            ]);

            $estado = Password::sendResetLink(
                $solicitud->only('email')
            );

            if ($estado == Password::RESET_LINK_SENT) {

                return back()->with('status', __($estado));
            }

            throw ValidationException::withMessages([
                'email' => [trans($estado)],
            ]);
        }
    }