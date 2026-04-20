<?php

    namespace App\Http\Requests\Auth;

    use App\Services\RecaptchaV3;
    use Illuminate\Auth\Events\Lockout;
    use Illuminate\Foundation\Http\FormRequest;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\RateLimiter;
    use Illuminate\Support\Str;
    use Illuminate\Validation\ValidationException;

    /**
     * FormRequest para el login web.
     *
     * Responsabilidades:
     * - Validar credenciales (email/password).
     * - Verificar reCAPTCHA v3 si está habilitado.
     * - Aplicar rate limiting para mitigar fuerza bruta.
     */
    class LoginRequest extends FormRequest {
        /**
         * Autorización del request.
         *
         * El login es accesible para usuarios no autenticados, por eso devuelve true.
         */
        public function authorize(): bool {

            return true;
        }

        /**
         * Reglas de validación.
         *
         * `recaptcha_token` se vuelve obligatorio solo si el servicio está activo.
         */
        public function rules(): array {

            $recaptcha = app(RecaptchaV3::class);

            return [
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
                // Token de reCAPTCHA v3: requerido si está habilitado.
                'recaptcha_token' => $recaptcha->isEnabled() ? ['required', 'string'] : ['nullable', 'string'],
            ];
        }

        /**
         * Intenta autenticar al usuario.
         *
         * - Primero valida que no se exceda el rate limit.
         * - Luego verifica reCAPTCHA (si aplica).
         * - Finalmente ejecuta `Auth::attempt`.
         */
        public function authenticate(): void {
            $this->ensureIsNotRateLimited();

            $recaptcha = app(RecaptchaV3::class);
            if ($recaptcha->isEnabled()) {
                // Acción esperada: `login`.
                $recaptcha->verifyOrFail(
                    (string) $this->input('recaptcha_token', ''),
                    (string) config('recaptcha.actions.login', 'login'),
                    $this->ip(),
                );
            }

            if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
                // Si falla el login, incrementa el contador de intentos.
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => trans('auth.failed'),
                ]);
            }

            // Si fue correcto, se limpian intentos fallidos acumulados.
            RateLimiter::clear($this->throttleKey());
        }

        /**
         * Lanza excepción si hay demasiados intentos recientes.
         */
        public function ensureIsNotRateLimited(): void {
            if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {

                return;
            }

            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        /**
         * Clave usada para el rate limiting.
         *
         * Combina email (normalizado) + IP para acotar intentos por origen.
         */
        public function throttleKey(): string {

            return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
        }
    }