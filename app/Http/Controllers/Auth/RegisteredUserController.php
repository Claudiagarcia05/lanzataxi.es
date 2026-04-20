<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use App\Models\User;
    use App\Rules\EmailDomainHasMx;
    use App\Services\RecaptchaV3;
    use Illuminate\Auth\Events\Registered;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Validation\Rules;
    use Inertia\Inertia;
    use Inertia\Response;

    /**
     * Registro de usuarios vía web (Inertia).
     *
     * Incluye:
     * - Validación del email (RFC/DNS en no-testing).
     * - Regla de dominio con MX (si no es testing).
     * - Verificación reCAPTCHA v3 si está habilitada.
     */
    class RegisteredUserController extends Controller {
        /**
         * Muestra el formulario de registro.
         */
        public function create(): Response {

            return Inertia::render('Auth/Register');
        }

        /**
         * Crea el usuario, dispara evento Registered y lo autentica.
         */
        public function store(Request $solicitud): RedirectResponse {
            $recaptcha = app(RecaptchaV3::class);

            $esTesting = app()->environment('testing');

            $validado = $solicitud->validate([
                'name' => 'required|string|max:255',
                'email' => array_values(array_filter([
                    'required',
                    'string',
                    'lowercase',
                    $esTesting ? 'email:rfc' : 'email:rfc,dns',
                    $esTesting ? null : new EmailDomainHasMx(),
                    'max:255',
                    'unique:' . User::class,
                ])),
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'recaptcha_token' => $recaptcha->isEnabled() ? 'required|string' : 'nullable|string',
            ]);

            if ($recaptcha->isEnabled()) {
                // Acción esperada: `register`.
                $recaptcha->verifyOrFail(
                    $validado['recaptcha_token'] ?? '',
                    (string) config('recaptcha.actions.register', 'register'),
                    $solicitud->ip(),
                );
            }

            $usuario = User::create([
                'name' => $solicitud->name,
                'email' => $solicitud->email,
                'password' => Hash::make($solicitud->password),
            ]);

            // Permite listeners (p.ej. enviar email de verificación).
            event(new Registered($usuario));

            // Login inmediato tras registro (flujo estándar Breeze/Jetstream).
            Auth::login($usuario);

            return redirect(route('dashboard', absolute: false));
        }
    }