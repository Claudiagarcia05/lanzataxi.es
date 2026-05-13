<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use App\Services\PasswordRecoveryService;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;
    use Inertia\Inertia;
    use Inertia\Response;

    /**
     * Solicitud de enlace de restablecimiento de contraseña.
     */
    class PasswordResetLinkController extends Controller {
        /**
         * Muestra el formulario “Olvidé mi contraseña”.
         */
        public function create(): Response {

            return Inertia::render('Auth/ForgotPassword', [
                'status' => session('status'),
            ]);
        }

        /**
         * Envía el código de recuperación al email si existe.
         */
        public function store(Request $solicitud, PasswordRecoveryService $passwordRecoveryService): JsonResponse {
            $solicitud->validate([
                'email' => 'required|email',
            ]);

            $passwordRecoveryService->requestCode((string) $solicitud->input('email'));

            return response()->json([
                'message' => 'Si el correo existe, hemos enviado un código de verificación.',
            ]);
        }

        /**
         * Verifica el código de 5 dígitos.
         */
        public function verify(Request $solicitud, PasswordRecoveryService $passwordRecoveryService): JsonResponse {
            $validado = $solicitud->validate([
                'email' => 'required|email',
                'code' => 'required|digits:5',
            ]);

            $token = $passwordRecoveryService->verifyCode(
                (string) $validado['email'],
                (string) $validado['code'],
            );

            return response()->json([
                'message' => 'Código verificado. Ya puedes escribir una nueva contraseña.',
                'token' => $token,
            ]);
        }

        /**
         * Aplica la nueva contraseña una vez verificado el código.
         */
        public function reset(Request $solicitud, PasswordRecoveryService $passwordRecoveryService): JsonResponse {
            $validado = $solicitud->validate([
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            ]);

            $passwordRecoveryService->resetPassword(
                (string) $validado['email'],
                (string) $validado['token'],
                (string) $validado['password'],
            );

            return response()->json([
                'message' => 'Contraseña actualizada correctamente.',
            ]);
        }
    }