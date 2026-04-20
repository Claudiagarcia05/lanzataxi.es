<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use Illuminate\Auth\Events\PasswordReset;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Password;
    use Illuminate\Support\Str;
    use Illuminate\Validation\Rules;
    use Illuminate\Validation\ValidationException;
    use Inertia\Inertia;
    use Inertia\Response;

    /**
     * Restablecimiento de contraseña (pantalla y acción).
     */
    class NewPasswordController extends Controller {
        /**
         * Muestra el formulario de reset (requiere token en la URL).
         */
        public function create(Request $solicitud): Response {

            return Inertia::render('Auth/ResetPassword', [
                'email' => $solicitud->email,
                'token' => $solicitud->route('token'),
            ]);
        }

        /**
         * Procesa el reseteo de contraseña.
         *
         * Usa el broker de passwords de Laravel, que valida token/email y ejecuta
         * el closure para persistir la nueva contraseña.
         */
        public function store(Request $solicitud): RedirectResponse {
            $solicitud->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $estado = Password::reset(
                $solicitud->only('email', 'password', 'password_confirmation', 'token'),
                function ($usuario) use ($solicitud) {
                    // `forceFill` evita problemas con atributos fillable/guarded.
                    $usuario->forceFill([
                        'password' => Hash::make($solicitud->password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    // Dispara evento estándar (auditoría/listeners).
                    event(new PasswordReset($usuario));
                }
            );

            if ($estado == Password::PASSWORD_RESET) {

                return redirect()->route('login')->with('status', __($estado));
            }

            throw ValidationException::withMessages([
                'email' => [trans($estado)],
            ]);
        }
    }