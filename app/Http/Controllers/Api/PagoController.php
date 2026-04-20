<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Pago;
    use App\Models\Viaje;
    use Illuminate\Http\Request;

    /**
     * Pagos asociados a viajes.
     *
     * Este controlador mezcla:
     * - Registro de pagos “manuales” (cash/card/app/etc.).
     * - Simulaciones de Stripe/PayPal (no integraciones completas).
     */
    class PagoController extends Controller {
        /**
         * Registra un pago para un viaje completado.
         *
         * Requiere que el usuario autenticado sea el pasajero del viaje.
         */
        public function store(Request $solicitud, viaje $viaje) {
            if ($viaje->pasajero_id !== $solicitud->user()->id) {

                return response()->json(['message' => 'No autorizado'], 403);
            }

            if ($viaje->status !== 'completed') {

                return response()->json(['message' => 'Solo se puede pagar viajes completados'], 400);
            }

            if ($viaje->pago) {

                return response()->json(['message' => 'Este viaje ya ha sido pagado'], 400);
            }

            $validado = $solicitud->validate([
                'method' => 'required|string|in:cash,card,app,stripe,paypal',
                'amount' => 'required|numeric|min:0',
                'transaction_id' => 'nullable|string|max:255',
            ]);

            $pago = Pago::create([
                'viaje_id' => $viaje->id,
                'method' => $validado['method'],
                'amount' => $validado['amount'],
                'status' => 'paid',
                'transaction_id' => $validado['transaction_id'] ?? null,
            ]);

            return response()->json($pago->load('viaje'), 201);
        }

        /**
         * Devuelve el pago de un viaje.
         *
         * Permitido al pasajero del viaje o al conductor asignado.
         */
        public function show(viaje $viaje) {
            if ($viaje->pasajero_id !== auth()->id() && $viaje->conductor?->user_id !== auth()->id()) {

                return response()->json(['message' => 'No autorizado'], 403);
            }

            $pago = $viaje->pago;

            if (!$pago) {

                return response()->json(['message' => 'No se encontró pago para este viaje'], 404);
            }

            return response()->json($pago);
        }

        /**
         * Simulación de pago con Stripe.
         *
         * Importante: no crea intents reales ni confirma con Stripe; solo persiste `Pago`.
         */
        public function processstripe(Request $solicitud, viaje $viaje) {
            $validado = $solicitud->validate([
                'pago_method_id' => 'required|string',
                'amount' => 'required|numeric|min:0',
            ]);
            
            try {                
                // `transaction_id` simulado para desarrollo.
                $pago = Pago::create([
                    'viaje_id' => $viaje->id,
                    'method' => 'stripe',
                    'amount' => $validado['amount'],
                    'status' => 'paid',
                    'transaction_id' => 'sim_' . uniqid(),
                ]);

                return response()->json([
                    'success' => true,
                    'pago' => $pago,
                    'message' => 'Pago procesado correctamente'
                ]);
            } catch (\Exception $e) {

                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar el pago: ' . $e->getMessage()
                ], 500);
            }
        }

        /**
         * Simulación de pago con PayPal.
         *
         * Importante: no valida el `order_id` contra PayPal; solo persiste `Pago`.
         */
        public function processPayPal(Request $solicitud, viaje $viaje) {
            $validado = $solicitud->validate([
                'order_id' => 'required|string',
                'amount' => 'required|numeric|min:0',
            ]);
            
            try {
                $pago = Pago::create([
                    'viaje_id' => $viaje->id,
                    'method' => 'paypal',
                    'amount' => $validado['amount'],
                    'status' => 'paid',
                    'transaction_id' => $validado['order_id'],
                ]);

                return response()->json([
                    'success' => true,
                    'pago' => $pago,
                    'message' => 'Pago procesado correctamente con PayPal'
                ]);
            } catch (\Exception $e) {

                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar el pago: ' . $e->getMessage()
                ], 500);
            }
        }
    }