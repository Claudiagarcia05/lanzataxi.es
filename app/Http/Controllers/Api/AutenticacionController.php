<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;

    class AutenticacionController extends Controller {
        public function register(Request $solicitud) {
            $validado = $solicitud->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email:rfc,dns|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'role' => 'nullable|in:pasajero,conductor,admin',
                'phone' => 'nullable|string|max:50',
            ], [
                'email.email' => 'El email debe ser una dirección válida y con dominio real.',
                'email.unique' => 'El email ya está registrado.',
            ]);

            // Regla de negocio: los administradores deben usar obligatoriamente emails @admin.com
            // - Si el rol es admin, el email debe terminar en @admin.com
            // - Si el email termina en @admin.com, el rol debe ser admin
            $rol = $validado['role'] ?? 'pasajero';
            $correo = strtolower(trim($validado['email'] ?? ''));
            $esCorreoAdmin = str_ends_with($correo, '@admin.com');

            $mensajeGenericoCredenciales = 'Credenciales inválidas. Por favor verifica tu email y contraseña.';

            if ($rol === 'admin' && !$esCorreoAdmin) {
                return response()->json([
                    'message' => $mensajeGenericoCredenciales,
                    'errors' => [
                        'email' => [$mensajeGenericoCredenciales],
                    ],
                ], 422);
            }

            if ($rol !== 'admin' && $esCorreoAdmin) {
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
                'role' => $validado['role'] ?? 'pasajero',
                'phone' => $validado['phone'] ?? null,
                'wallet_balance' => 0,
                'is_disabled' => false,
            ]);

            if (($validado['role'] ?? 'pasajero') === 'conductor') {
                $conductor = \App\Models\Conductor::create([
                    'user_id' => $usuario->id,
                    'license_number' => 'LIC-' . strtoupper(uniqid()),
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