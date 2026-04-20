<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Http\Request;
    use Inertia\Inertia;
    use Inertia\Response;

    /**
     * Pantalla “verifica tu email”.
     *
     * Se implementa como controlador invocable para simplificar el routing.
     */
    class EmailVerificationPromptController extends Controller {
        /**
         * Si el email ya está verificado, redirige al dashboard; si no, muestra la vista.
         */
        public function __invoke(Request $solicitud): RedirectResponse|Response {

            return $solicitud->user()->hasVerifiedEmail()
                        ? redirect()->intended(route('dashboard', absolute: false))
                        : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
        }
    }