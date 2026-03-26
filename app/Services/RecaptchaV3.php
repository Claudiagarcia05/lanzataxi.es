<?php

    namespace App\Services;

    use Illuminate\Http\Client\ConnectionException;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Validation\ValidationException;

    class RecaptchaV3 {
        public function isEnabled(): bool {

            return (bool) config('recaptcha.enabled')
                && (string) config('recaptcha.secret_key');
        }

        /**
         * Verifica un token reCAPTCHA v3 contra Google y lanza ValidationException si falla.
         */
        public function verifyOrFail(string $token, string $expectedAction, ?string $ip = null): array {
            if (!$this->isEnabled()) {

                return ['skipped' => true];
            }

            $token = trim((string) $token);
            if ($token === '') {

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

                $this->fail();
            }

            $data = (array) $respuesta->json();

            $success = (bool) ($data['success'] ?? false);
            $action = (string) ($data['action'] ?? '');
            $score = (float) ($data['score'] ?? 0);
            $hostname = (string) ($data['hostname'] ?? '');

            if (!$success) {

                $this->fail();
            }

            if ($action !== $expectedAction) {

                $this->fail();
            }

            $minScore = (float) config('recaptcha.min_score', 0.5);
            if ($score < $minScore) {

                $this->fail();
            }

            $hostnames = (array) config('recaptcha.hostnames', []);
            $expectedHostname = (string) config('recaptcha.hostname');

            if ($expectedHostname !== '') {
                $hostnames[] = $expectedHostname;
            }

            $hostnames = array_values(array_unique(array_filter(array_map('strval', $hostnames))));

            if (!empty($hostnames) && !in_array($hostname, $hostnames, true)) {

                $this->fail();
            }

            return $data;
        }

        private function fail(): void {
            throw ValidationException::withMessages([
                'recaptcha' => ['Verificación anti-bots falló. Inténtalo de nuevo.'],
            ]);
        }
    }
