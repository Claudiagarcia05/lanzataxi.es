<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Validation\Rules\Password;

    /**
     * Cambio de contraseña para usuario autenticado.
     */
    class PasswordController extends Controller {
        /**
         * Actualiza la contraseña verificando la contraseña actual.
         */
        public function update(Request $solicitud): RedirectResponse {
            $validated = $solicitud->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
            ]);

            // Hash seguro (bcrypt/argon según config).
            $solicitud->user()->update([
                'password' => Hash::make($validated['password']),
            ]);

            return back();
        }
    }