<?php

    namespace App\Http\Requests;

    use App\Models\User;
    use App\Rules\EmailDomainHasMx;
    use Illuminate\Foundation\Http\FormRequest;
    use Illuminate\Validation\Rule;

    /**
     * FormRequest para actualizar el perfil del usuario.
     *
     * Centraliza reglas de validación para mantener el controlador limpio.
     * Nota: aquí no se define `authorize()`, por lo que aplica el comportamiento
     * por defecto de `FormRequest` (en Laravel suele ser `false` si no se sobrescribe).
     * En este proyecto se usa con rutas que ya están protegidas por auth.
     */
    class ProfileUpdateRequest extends FormRequest {
        /**
         * Reglas de validación del formulario.
         *
         * - En `testing` se relajan validaciones (sin DNS/MX) para evitar dependencia
         *   de red durante tests.
         * - El email debe ser único ignorando al usuario actual.
         */
        public function rules(): array {
            $esTesting = app()->environment('testing');

            return [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    $esTesting ? 'email:rfc' : 'email:rfc,dns',
                    // Regla custom: exige dominio con registro MX (excepto en testing).
                    $esTesting ? null : new EmailDomainHasMx(),
                    'max:255',
                    // Único, pero permite mantener el propio email sin fallar.
                    Rule::unique(User::class)->ignore($this->user()->id),
                ],
            ];
        }
    }