<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use Illuminate\Auth\Events\Verified;
    use Illuminate\Foundation\Auth\EmailVerificationRequest;
    use Illuminate\Http\RedirectResponse;

    /**
     * Confirmación de email.
     *
     * Se invoca desde el enlace firmado que envía Laravel.
     */
    class VerifyEmailController extends Controller {
        /**
         * Marca el email como verificado (si procede) y redirige al dashboard.
         */
        public function __invoke(EmailVerificationRequest $solicitud): RedirectResponse {
            if ($solicitud->user()->hasVerifiedEmail()) {

                return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
            }

            if ($solicitud->user()->markEmailAsVerified()) {
                // Evento estándar de Laravel (para listeners/telemetría).
                event(new Verified($solicitud->user()));
            }

            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }
    }