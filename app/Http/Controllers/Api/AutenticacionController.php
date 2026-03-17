<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Conductor;
    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Str;

    class AutenticacionController extends Controller {
        public function register(Request $solicitud) {
            $validado = $solicitud->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email:rfc,dns|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                // Nota: el rol admin NO se crea por registro público. Solo pasajero/conductor.
                'role' => 'nullable|in:pasajero,conductor',
                'phone' => 'nullable|string|max:50',
            ], [
                'email.email' => 'El email debe ser una dirección válida y con dominio real.',
                'email.unique' => 'El email ya está registrado.',
            ]);

            // Reglas de negocio (registro público):
            // - Los emails @admin.es están reservados: no se permite registrarlos públicamente.
            // - Coherencia Taxista ⇔ @taxi.es
            //   - Si el rol es conductor, el email debe terminar en @taxi.es
            //   - Si el email termina en @taxi.es, el rol debe ser conductor
            $rol = $validado['role'] ?? 'pasajero';
            $correo = strtolower(trim($validado['email'] ?? ''));
            $esCorreoAdmin = str_ends_with($correo, '@admin.es');
            $esCorreoConductor = str_ends_with($correo, '@taxi.es');

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

        public function login(Request $solicitud) {
            $validado = $solicitud->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

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

            // Regla de negocio: un taxista debe estar aprobado por un admin.
            if (($usuario->role ?? null) === 'conductor') {
                $usuario->loadMissing('conductor');
                if (($usuario->conductor?->approval_status ?? null) !== 'approved') {
                    return response()->json([
                        'message' => 'No tienes permiso de taxista',
                    ], 403);
                }
            }

            $usuario->tokens()->delete();

            $token = $usuario->createToken('api')->plainTextToken;

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

        public function me(Request $solicitud) {

            return response()->json($solicitud->user());
        }

        public function logout(Request $solicitud) {

            $token = $solicitud->user()->currentAccessToken();
            if ($token && $token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $token->delete();
            }

            return response()->json(['message' => 'Sesión cerrada']);
        }
    }