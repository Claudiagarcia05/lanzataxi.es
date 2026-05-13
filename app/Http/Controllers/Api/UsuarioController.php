<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Rules\EmailDomainHasMx;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Validation\Rules\Password;

    /**
     * Perfil del usuario (datos, avatar, contraseña, preferencias).
     */
    class UsuarioController extends Controller {
        /**
         * Actualiza campos del perfil.
         *
         * Implementación: reglas de validación dinámicas basadas en qué campos llegan
         * en la petición (evita exigir campos no enviados).
         */
        public function updateProfile(Request $solicitud) {
            $usuario = $solicitud->user();

            $rules = [];
            if ($solicitud->has('name')) {
                $rules['name'] = 'string|max:255';
            }
            if ($solicitud->has('email')) {
                $rules['email'] = array_values(array_filter([
                    'email:rfc,dns',
                    app()->environment('testing') ? null : new EmailDomainHasMx(),
                    'unique:users,email,' . $usuario->id,
                ]));
            }
            if ($solicitud->has('phone')) {
                $rules['phone'] = 'nullable|string|max:20';
            }
            if ($solicitud->has('phone_alternative')) {
                $rules['phone_alternative'] = 'nullable|string|max:20';
            }
            if ($solicitud->hasFile('avatar')) {
                $rules['avatar'] = 'image|max:2048';
            }

            $validado = $solicitud->validate($rules);

            if (isset($validado['name'])) {
                $usuario->name = $validado['name'];
            }
            if (isset($validado['email'])) {
                $usuario->email = $validado['email'];
            }
            if (isset($validado['phone'])) {
                $usuario->phone = $validado['phone'];
            }

            if ($solicitud->hasFile('avatar')) {
                if ($usuario->avatar) {
                    // Limpieza del avatar anterior para no dejar basura en storage.
                    Storage::disk('public')->delete($usuario->avatar);
                }
                
                $ruta = $solicitud->file('avatar')->store('avatars', 'public');
                $usuario->avatar = $ruta;
            }

            $usuario->save();

            if ($usuario->PerfilPasajero && isset($validado['phone_alternative'])) {
                $usuario->PerfilPasajero->update([
                    'phone_alternative' => $validado['phone_alternative'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente',
                'user' => $usuario->fresh()
            ]);
        }

        /**
         * Sube avatar.
         *
         * Incluye checks explícitos de mime/tamaño para respuestas más claras.
         */
        public function uploadAvatar(Request $solicitud) {
            if (!$solicitud->hasFile('avatar')) {

                return response()->json([
                    'success' => false,
                    'message' => 'No se recibió ningún archivo',
                    'debug' => [
                        'has_file' => $solicitud->hasFile('avatar'),
                        'all_files' => $solicitud->allFiles(),
                        'content_type' => $solicitud->header('Content-Type')
                    ]
                ], 422);
            }

            $archivo = $solicitud->file('avatar');

            if (!$archivo->isValid()) {

                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no es válido',
                    'error' => $archivo->getErrorMessage()
                ], 422);
            }

            $mimesPermitidos = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp', 'image/avif'];
            if (!in_array($archivo->getMimeType(), $mimesPermitidos)) {

                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de archivo no permitido. Formatos aceptados: JPG, PNG, GIF, WEBP, AVIF',
                    'mime_type' => $archivo->getMimeType()
                ], 422);
            }

            if ($archivo->getSize() > 2048 * 1024) {

                return response()->json([
                    'success' => false,
                    'message' => 'El archivo es demasiado grande (máximo 2MB)'
                ], 422);
            }

            $usuario = $solicitud->user();

            if ($usuario->avatar && Storage::disk('public')->exists($usuario->avatar)) {
                // Borra el anterior si existe.
                Storage::disk('public')->delete($usuario->avatar);
            }

            $ruta = $archivo->store('avatars', 'public');
            $usuario->avatar = $ruta;
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Avatar actualizado correctamente',
                'avatar' => $ruta
            ]);
        }

        /**
         * Actualiza la contraseña del usuario.
         */
        public function updatePassword(Request $solicitud) {
            $validado = $solicitud->validate([
                'new_password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            ]);

            $usuario = $solicitud->user();

            // El modelo User ya define el cast `password => hashed`, así que
            // asignamos el valor en claro y dejamos que Laravel lo hashee.
            $usuario->update([
                'password' => $validado['new_password'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente'
            ]);
        }

        /**
         * Actualiza preferencias del perfil de pasajero.
         *
         * Nota: se guardan como JSON string.
         */
        public function updatePreferences(Request $solicitud) {
            $validado = $solicitud->validate([
                'preferences' => 'required|array',
            ]);

            $usuario = $solicitud->user();

            if ($usuario->PerfilPasajero) {
                $usuario->PerfilPasajero->update([
                    'preferences' => json_encode($validado['preferences'])
                ]);
            } else {
                $usuario->PerfilPasajero()->create([
                    'preferences' => json_encode($validado['preferences'])
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Preferencias actualizadas correctamente'
            ]);
        }

        /**
         * Elimina la cuenta del usuario autenticado.
         *
         * Nota: borra avatar del storage público si existía.
         */
        public function deleteAccount(Request $solicitud) {
            $usuario = $solicitud->user();
            
            if ($usuario->avatar) {
                Storage::disk('public')->delete($usuario->avatar);
            }
            
            $usuario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta eliminada correctamente'
            ]);
        }
    }