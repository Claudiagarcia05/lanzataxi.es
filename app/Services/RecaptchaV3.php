<?php

    namespace App\Services;

    use Illuminate\Http\Client\ConnectionException;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Validation\ValidationException;

    /**
     * Servicio de verificación reCAPTCHA v3.
     *
     * Centraliza la validación del token de reCAPTCHA contra el endpoint
     * oficial de Google y aplica las reglas de seguridad configuradas:
     * - `action` esperado (evita reutilización del token en otro flujo)
     * - `score` mínimo (umbral anti-bot)
     * - `hostname` permitido (mitiga tokens emitidos para otros dominios)
     *
     * Configuración esperada en `config/recaptcha.php`:
     * - enabled (bool)
     * - secret_key (string)
     * - timeout (int, segundos)
     * - min_score (float)
     * - hostname (string) / hostnames (array)
     */
    class RecaptchaV3 {
        /**
         * Indica si la verificación está habilitada.
         *
         * Se considera habilitada si `recaptcha.enabled` es true y existe
         * `recaptcha.secret_key`.
         */
        public function isEnabled(): bool {

            return (bool) config('recaptcha.enabled')
                && (string) config('recaptcha.secret_key');
        }

        /**
         * Verifica un token de reCAPTCHA v3 y lanza excepción si falla.
         *
         * @param string      $token           Token generado en el frontend.
         * @param string      $expectedAction  Acción esperada (p.ej. "login", "register").
         * @param string|null $ip              IP remota opcional (remoteip).
         *
         * @return array Respuesta completa del endpoint (incluye `score`, `action`, etc.).
         *               Si reCAPTCHA está deshabilitado, devuelve `['skipped' => true]`.
         *
         * @throws ValidationException Si la verificación anti-bots falla.
         */
        public function verifyOrFail(string $token, string $expectedAction, ?string $ip = null): array {
            if (!$this->isEnabled()) {
                // Cuando está desactivado por configuración, se omite la validación.

                return ['skipped' => true];
            }

            $token = trim((string) $token);
            if ($token === '') {
                // Token vacío: tratamos como fallo de verificación.

                $this->fail();
            }

            $endpoint = 'https://www.google.com/recaptcha/api/siteverify';

            try {
                $respuesta = Http::asForm()
                    ->timeout((int) config('recaptcha.timeout', 5))
                    ->post($endpoint, array_filter([
                        'secret' => (string) config('recaptcha.secret_key'),
                        'response' => $token,
                        'remoteip' => $ip,
                    ], fn ($value) => $value !== null && $value !== ''));
            } catch (ConnectionException $e) {
                // Si no se puede contactar con Google (timeout/red), por seguridad fallamos.

                $this->fail();
            }

            $data = (array) $respuesta->json();

            $success = (bool) ($data['success'] ?? false);
            $action = (string) ($data['action'] ?? '');
            $score = (float) ($data['score'] ?? 0);
            $hostname = (string) ($data['hostname'] ?? '');

            if (!$success) {
                // El endpoint indica que el token no es válido.

                $this->fail();
            }

            if ($action !== $expectedAction) {
                // Protección: el token debe corresponder a la acción/flujo esperado.

                $this->fail();
            }

            $minScore = (float) config('recaptcha.min_score', 0.5);
            if ($score < $minScore) {
                // Umbral de sospecha: score bajo => se considera bot.

                $this->fail();
            }

            $hostnames = (array) config('recaptcha.hostnames', []);
            $expectedHostname = (string) config('recaptcha.hostname');

            if ($expectedHostname !== '') {
                $hostnames[] = $expectedHostname;
            }

            $hostnames = array_values(array_unique(array_filter(array_map('strval', $hostnames))));

            if (!empty($hostnames) && !in_array($hostname, $hostnames, true)) {
                // Si se configuraron hostnames, el hostname del token debe coincidir.

                $this->fail();
            }

            return $data;
        }

        /**
         * Lanza una excepción de validación estándar para integrarse con Form Requests.
         */
        private function fail(): void {
            throw ValidationException::withMessages([
                'recaptcha' => ['Verificación anti-bots falló. Inténtalo de nuevo.'],
            ]);
        }
    }