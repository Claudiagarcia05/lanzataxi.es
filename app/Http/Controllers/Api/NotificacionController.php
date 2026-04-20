<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;

    /**
     * Notificaciones del usuario.
     *
     * Nota: actualmente devuelve datos simulados. Para una implementación real,
     * debería consultarse una tabla de notificaciones o integrarse con un sistema
     * push (FCM/APNS) y mantener estado `read_at` persistente.
     */
    class NotificacionController extends Controller {
        /**
         * Lista notificaciones (stub).
         */
        public function index(Request $solicitud) {
            $notificaciones = [
                [
                    'id' => 1,
                    'type' => 'viaje_accepted',
                    'title' => 'Viaje aceptado',
                    'message' => 'Tu viaje ha sido aceptado por un conductor',
                    'data' => ['viaje_id' => 1],
                    'read_at' => null,
                    'created_at' => now()->subMinutes(5)->toISOString(),
                ],
                [
                    'id' => 2,
                    'type' => 'viaje_completed',
                    'title' => 'Viaje completado',
                    'message' => 'Tu viaje ha sido completado. Recuerda valorar al conductor',
                    'data' => ['viaje_id' => 2],
                    'read_at' => now()->subMinutes(2)->toISOString(),
                    'created_at' => now()->subHours(1)->toISOString(),
                ],
            ];

            return response()->json($notificaciones);
        }

        /**
         * Marca una notificación como leída (stub).
         */
        public function markAsRead(Request $solicitud, $notificationId) {
            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ]);
        }

        /**
         * Marca todas las notificaciones como leídas (stub).
         */
        public function markAllAsRead(Request $solicitud) {
            return response()->json([
                'success' => true,
                'message' => 'Todas las notificaciones marcadas como leidas'
            ]);
        }

        /**
         * Elimina una notificación (stub).
         */
        public function destroy(Request $solicitud, $notificationId) {
            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada'
            ]);
        }
    }