<?php

    namespace App\Rules;

    use Closure;
    use Illuminate\Contracts\Validation\ValidationRule;

    /**
     * Regla de validación: el dominio del email debe tener registros DNS MX.
     *
     * Objetivo: reducir emails con dominios inexistentes o mal configurados.
     *
     * Importante:
     * - Esta regla NO sustituye la validación `email` de Laravel; se recomienda
     *   usarla en combinación.
     * - Si el valor es vacío o no parece un email, no falla aquí (se asume que
     *   otras reglas se encargan de esos casos).
     * - Depende de `checkdnsrr()`; si no está disponible en el entorno, la
     *   regla no puede comprobar DNS y no falla.
     */
    class EmailDomainHasMx implements ValidationRule {
        /**
         * Ejecuta la validación.
         *
         * @param string  $attribute Nombre del atributo validado.
         * @param mixed   $value     Valor recibido.
         * @param Closure $fail      Callback para registrar el error.
         */
        public function validate(string $attribute, mixed $value, Closure $fail): void {
            $email = trim((string) $value);

            if ($email === '' || !str_contains($email, '@')) {
                // No se marca como error aquí: otras reglas (required/email) deben hacerlo.

                return;
            }

            $parts = explode('@', $email);
            if (count($parts) !== 2) {
                // Formato inesperado; se delega a la regla `email`.

                return;
            }

            $domain = rtrim(strtolower(trim($parts[1])), '.');
            if ($domain === '') {

                return;
            }

            if (!function_exists('checkdnsrr')) {
                // Entorno sin soporte de DNS lookup; evitamos falsos negativos.

                return;
            }

            if (!checkdnsrr($domain, 'MX')) {
                // No hay registros MX: dominio no apto para recepción de correo.
                $fail('El dominio del email no es válido (no tiene registros MX).');
            }
        }
    }