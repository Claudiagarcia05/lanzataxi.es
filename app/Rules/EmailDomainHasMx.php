<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailDomainHasMx implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = trim((string) $value);

        // Dejar que la regla `email:*` gestione formatos claramente inválidos.
        if ($email === '' || !str_contains($email, '@')) {
            return;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return;
        }

        $domain = rtrim(strtolower(trim($parts[1])), '.');
        if ($domain === '') {
            return;
        }

        // Si la función no existe por entorno, no bloqueamos el registro.
        if (!function_exists('checkdnsrr')) {
            return;
        }

        if (!checkdnsrr($domain, 'MX')) {
            $fail('El dominio del email no es válido (no tiene registros MX).');
        }
    }
}
