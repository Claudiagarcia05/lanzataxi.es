<?php

namespace App\Services;

use App\Mail\PasswordRecoveryCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordRecoveryService {
    private const CACHE_PREFIX = 'password-recovery:';
    private const CODE_TTL_MINUTES = 15;
    private const MAX_ATTEMPTS = 5;

    public function requestCode(string $email): bool {
        $emailNormalizado = $this->normalizeEmail($email);
        $usuario = $this->findUserByEmail($emailNormalizado);

        if (!$usuario) {
            return false;
        }

        $code = (string) random_int(10000, 99999);

        Cache::put($this->cacheKey($emailNormalizado), [
            'email' => $emailNormalizado,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'verified' => false,
            'verify_token' => null,
            'requested_at' => now()->toIso8601String(),
        ], now()->addMinutes(self::CODE_TTL_MINUTES));

        Mail::to($usuario->email)->send(new PasswordRecoveryCodeMail($code, self::CODE_TTL_MINUTES));

        return true;
    }

    public function verifyCode(string $email, string $code): string {
        $emailNormalizado = $this->normalizeEmail($email);
        $estado = $this->getRecoveryState($emailNormalizado);

        if (!$estado) {
            throw ValidationException::withMessages([
                'code' => ['El código no es válido o ha caducado.'],
            ]);
        }

        if (($estado['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            Cache::forget($this->cacheKey($emailNormalizado));

            throw ValidationException::withMessages([
                'code' => ['Has superado el número máximo de intentos. Solicita un nuevo código.'],
            ]);
        }

        if (!Hash::check($code, (string) ($estado['code_hash'] ?? ''))) {
            $estado['attempts'] = ((int) ($estado['attempts'] ?? 0)) + 1;
            Cache::put($this->cacheKey($emailNormalizado), $estado, now()->addMinutes(self::CODE_TTL_MINUTES));

            throw ValidationException::withMessages([
                'code' => ['El código no es válido o ha caducado.'],
            ]);
        }

        $token = Str::random(64);
        $estado['verified'] = true;
        $estado['verify_token'] = $token;
        $estado['verified_at'] = now()->toIso8601String();
        Cache::put($this->cacheKey($emailNormalizado), $estado, now()->addMinutes(self::CODE_TTL_MINUTES));

        return $token;
    }

    public function resetPassword(string $email, string $token, string $password): void {
        $emailNormalizado = $this->normalizeEmail($email);
        $estado = $this->getRecoveryState($emailNormalizado);

        if (!$estado || !($estado['verified'] ?? false) || ($estado['verify_token'] ?? null) !== $token) {
            throw ValidationException::withMessages([
                'email' => ['La recuperación no es válida o ha caducado.'],
            ]);
        }

        $usuario = $this->findUserByEmail($emailNormalizado);

        if (!$usuario) {
            Cache::forget($this->cacheKey($emailNormalizado));

            throw ValidationException::withMessages([
                'email' => ['La recuperación no es válida o ha caducado.'],
            ]);
        }

        $usuario->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        if (method_exists($usuario, 'tokens')) {
            $usuario->tokens()->delete();
        }

        Cache::forget($this->cacheKey($emailNormalizado));
    }

    private function findUserByEmail(string $email): ?User {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    private function getRecoveryState(string $email): ?array {
        $estado = Cache::get($this->cacheKey($email));

        return is_array($estado) ? $estado : null;
    }

    private function cacheKey(string $email): string {
        return self::CACHE_PREFIX . $email;
    }

    private function normalizeEmail(string $email): string {
        return mb_strtolower(trim($email));
    }
}