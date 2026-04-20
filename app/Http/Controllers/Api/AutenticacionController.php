<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Services\RecaptchaV3;
    use App\Models\Conductor;
    use App\Models\User;
    use App\Rules\EmailDomainHasMx;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Str;

    /**
     * Registro / Login vía API.
     *
     * Incluye validación con reCAPTCHA v3 (si está habilitado) y reglas de
     * dominio para diferenciar admin / conductor / pasajero.
     */
    class AutenticacionController extends Controller {
        /**
         * Registra un usuario.
         *
         * Reglas de rol/email:
         * - Admin: NO se permite registrar desde este endpoint (bloqueo por dominio @admin.es).
         * - Conductor: debe usar dominio @taxi.es y queda en estado `pending` hasta aprobación.
         */
        public function register(Request $solicitud) {
            $recaptcha = app(RecaptchaV3::class);

            $validado = $solicitud->validate([
                'name' => 'required|string|max:255',
                'email' => array_values(array_filter([
                    'required',
                    'email:rfc,dns',
                    app()->environment('testing') ? null : new EmailDomainHasMx(),
                    'unique:users,email',
                ])),
                'password' => 'required|string|min:6|confirmed',
                'role' => 'nullable|in:pasajero,conductor',
                'phone' => 'nullable|string|max:50',
                'recaptcha_token' => $recaptcha->isEnabled() ? 'required|string' : 'nullable|string',
            ], [
                'email.email' => 'El email debe ser una dirección válida y con dominio real.',
                'email.unique' => 'El email ya está registrado.',
            ]);

            if ($recaptcha->isEnabled()) {
                // Verifica el token con la acción esperada para reducir abuso/bots.
                $recaptcha->verifyOrFail(
                    $validado['recaptcha_token'] ?? '',
                    (string) config('recaptcha.actions.register', 'register'),
                    $solicitud->ip(),
                );
            }

            $rol = $validado['role'] ?? 'pasajero';
            $correo = strtolower(trim($validado['email'] ?? ''));
            $esCorreoAdmin = str_ends_with($correo, '@admin.es');
            $esCorreoConductor = str_ends_with($correo, '@taxi.es');

            // Mensaje genérico para evitar filtrar reglas internas (dominios/roles).
            $mensajeGenericoCredenciales = 'Credenciales inválidas. Por favor verifica tu email y contraseña.';

            if ($esCorreoAdmin) {
                
                return response()->json([
                    'message' => $mensajeGenericoCredenciales,
                    'errors' => [
                        'email' => [$mensajeGenericoCredenciales],
                    ],
                ], 422);
            }

            if ($rol === 'conductor' && !$esCorreoConductor) {

                return response()->json([
                    'message' => $mensajeGenericoCredenciales,
                    'errors' => [
                        'email' => [$mensajeGenericoCredenciales],
                    ],
                ], 422);
            }

            if ($rol !== 'conductor' && $esCorreoConductor) {

                return response()->json([
                    'message' => $mensajeGenericoCredenciales,
                    'errors' => [
                        'role' => [$mensajeGenericoCredenciales],
                    ],
                ], 422);
            }

            $usuario = User::create([
                'name' => $validado['name'],
                'email' => $validado['email'],
                'password' => Hash::make($validado['password']),
                'role' => $rol,
                'phone' => $validado['phone'] ?? null,
                'wallet_balance' => 0,
                'is_disabled' => false,
            ]);

            if ($rol === 'conductor') {
                // Crea el perfil de conductor en estado pendiente y asegura que exista un taxi asociado.
                $licencia = 'LIC-' . Str::upper(Str::random(10));
                while (Conductor::where('license_number', $licencia)->exists()) {
                    $licencia = 'LIC-' . Str::upper(Str::random(10));
                }

                $conductor = \App\Models\Conductor::create([
                    'user_id' => $usuario->id,
                    'license_number' => $licencia,
                    'rating' => 5.0,
                    'is_active' => false,
                    'approval_status' => 'pending',
                    'approved_at' => null,
                    'rejected_at' => null,
                ]);

                $conductor->ensureTaxiExists('offline');
            }

            // Token Sanctum para autenticación vía API.
            $token = $usuario->createToken('api')->plainTextToken;

            return response()->json([
                'message' => 'Usuario registrado correctamente',
                'token' => $token,
                'user' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'role' => $usuario->role,
                    'phone' => $usuario->phone,
                    'avatar' => $usuario->avatar,
                    'wallet_balance' => $usuario->wallet_balance ?? 0,
                ],
            ], 201);
        }

        /**
         * Inicia sesión y devuelve un token.
         *
         * Consideraciones:
         * - Si la cuenta está deshabilitada se devuelve 403.
         * - Si el usuario es conductor, debe estar aprobado para poder acceder.
         */
        public function login(Request $solicitud) {
            $recaptcha = app(RecaptchaV3::class);

            $validado = $solicitud->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'recaptcha_token' => $recaptcha->isEnabled() ? 'required|string' : 'nullable|string',
            ]);

            if ($recaptcha->isEnabled()) {
                // Verifica el token con la acción esperada para reducir abuso/bots.
                $recaptcha->verifyOrFail(
                    $validado['recaptcha_token'] ?? '',
                    (string) config('recaptcha.actions.login', 'login'),
                    $solicitud->ip(),
                );
            }

            $usuario = User::where('email', $validado['email'])->first();

            if (!$usuario || !Hash::check($validado['password'], $usuario->password)) {

                return response()->json([
                    'message' => 'Credenciales inválidas. Por favor verifica tu email y contraseña.',
                    'errors' => [
                        'email' => ['Las credenciales proporcionadas no coinciden con nuestros registros.']
                    ]
                ], 401);
            }

            if (!empty($usuario->is_disabled)) {

                return response()->json([
                    'message' => 'Tu cuenta está desactivada.',
                ], 403);
            }

            if (($usuario->role ?? null) === 'conductor') {
                $usuario->loadMissing('conductor');
                if (($usuario->conductor?->approval_status ?? null) !== 'approved') {

                    return response()->json([
                        'message' => 'No tienes permiso de taxista',
                    ], 403);
                }
            }

            // Revoca tokens anteriores para mantener una sola sesión API activa.
            $usuario->tokens()->delete();

            $token = $usuario->createToken('api')->plainTextToken;

            // Mantiene sesión web si aplica (además del token API).
            auth()->login($usuario);

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'role' => $usuario->role,
                    'phone' => $usuario->phone,
                    'avatar' => $usuario->avatar,
                    'wallet_balance' => $usuario->wallet_balance ?? 0,
                ],
                'message' => 'Inicio de sesión exitoso'
            ]);
        }

        /**
         * Devuelve el usuario autenticado.
         */
        public function me(Request $solicitud) {
            return response()->json($solicitud->user());
        }

        /**
         * Cierra la sesión API eliminando el token actual.
         */
        public function logout(Request $solicitud) {
            $token = $solicitud->user()->currentAccessToken();
            if ($token && $token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $token->delete();
            }

            return response()->json(['message' => 'Sesión cerrada']);
        }
    }