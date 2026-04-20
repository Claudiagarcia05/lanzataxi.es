<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Http\Request;

    /**
     * Reenvío del email de verificación.
     */
    class EmailVerificationNotificationController extends Controller {
        /**
         * Envía el email de verificación si el usuario aún no está verificado.
         */
        public function store(Request $solicitud): RedirectResponse {
            if ($solicitud->user()->hasVerifiedEmail()) {

                return redirect()->intended(route('dashboard', absolute: false));
            }

            // Notificación estándar de Laravel (puede estar configurada en mail).
            $solicitud->user()->sendEmailVerificationNotification();

            return back()->with('status', 'verification-link-sent');
        }
    }