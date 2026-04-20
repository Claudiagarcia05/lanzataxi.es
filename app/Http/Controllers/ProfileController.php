<?php

    namespace App\Http\Controllers;

    use App\Http\Requests\ProfileUpdateRequest;
    use Illuminate\Contracts\Auth\MustVerifyEmail;
    use Illuminate\Http\RedirectResponse;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Redirect;
    use Inertia\Inertia;
    use Inertia\Response;

    /**
     * Gestión del perfil del usuario autenticado (web/Inertia).
     */
    class ProfileController extends Controller {
        /**
         * Muestra la pantalla de edición de perfil.
         */
        public function edit(Request $solicitud): Response {

            return Inertia::render('Profile/Edit', [
                'mustVerifyEmail' => $solicitud->user() instanceof MustVerifyEmail,
                'status' => session('status'),
            ]);
        }

        /**
         * Actualiza los datos del perfil.
         *
         * Importante: si cambia el email, se invalida la verificación (`email_verified_at = null`)
         * para obligar a verificar la nueva dirección.
         */
        public function update(ProfileUpdateRequest $solicitud): RedirectResponse {
            $solicitud->user()->fill($solicitud->validated());

            if ($solicitud->user()->isDirty('email')) {
                // Al cambiar email, se exige volver a verificar.
                $solicitud->user()->email_verified_at = null;
            }

            $solicitud->user()->save();

            return Redirect::route('profile.edit');
        }

        /**
         * Elimina la cuenta del usuario.
         *
         * Seguridad: exige contraseña actual antes de borrar.
         * Orden:
         * - Valida password.
         * - Cierra sesión.
         * - Borra usuario.
         * - Invalida sesión y regenera CSRF.
         */
        public function destroy(Request $solicitud): RedirectResponse {
            $solicitud->validate([
                'password' => ['required', 'current_password'],
            ]);

            $usuario = $solicitud->user();

            Auth::guard('web')->logout();

            // Elimina la fila del usuario (soft delete si el modelo lo define).
            $usuario->delete();

            // Limpieza de sesión tras eliminar la cuenta.
            $solicitud->session()->invalidate();
            $solicitud->session()->regenerateToken();

            return Redirect::to('/');
        }
    }