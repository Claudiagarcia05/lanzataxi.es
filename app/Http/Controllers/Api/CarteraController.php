<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Deuda;
    use App\Models\User;
    use App\Models\Viaje;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;

    /**
     * Cartera virtual del usuario (saldo y gestión de deudas).
     *
     * Importante: varias operaciones usan `DB::transaction()` y `lockForUpdate()`
     * para evitar condiciones de carrera al modificar saldos.
     */
    class CarteraController extends Controller {
        /**
         * Liquida deudas pendientes si el saldo del usuario lo permite.
         *
         * Devuelve un resumen para que el caller decida si continuar.
         */
        private function liquidarDeudasPendientesSiEsPosible(User $usuario): array {
            $resultado = [
                'had_debt' => false,
                'settled' => false,
                'pending_debt' => 0.0,
            ];

            // Se bloquean filas para que dos recargas simultáneas no liquiden lo mismo dos veces.
            $deudas = Deuda::query()
                ->where('user_id', $usuario->id)
                ->where('status', 'pending')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $deudaTotal = (float) $deudas->sum('amount');
            $resultado['pending_debt'] = $deudaTotal;
            if ($deudaTotal <= 0) {

                return $resultado;
            }

            $resultado['had_debt'] = true;
            $saldo = (float) ($usuario->wallet_balance ?? 0);
            if ($saldo < $deudaTotal) {

                return $resultado;
            }

            // Se descuenta el total de la deuda del saldo del pasajero.
            $usuario->wallet_balance = $saldo - $deudaTotal;
            $usuario->save();

            foreach ($deudas as $deuda) {
                $deuda->status = 'paid';
                $deuda->save();

                if (!$deuda->trip_id) {
                    continue;
                }

                // Si la deuda se relaciona con un viaje, se abona el importe al conductor.
                $viaje = Viaje::query()->with(['conductor', 'pago'])->find($deuda->trip_id);
                if (!$viaje || !$viaje->conductor) {
                    continue;
                }

                $usuarioConductor = User::query()
                    ->whereKey($viaje->conductor->user_id)
                    ->lockForUpdate()
                    ->first();

                if ($usuarioConductor) {
                    $usuarioConductor->wallet_balance = (float) ($usuarioConductor->wallet_balance ?? 0) + (float) ($deuda->amount ?? 0);
                    $usuarioConductor->save();
                }

                if ($viaje->pago && ($viaje->pago->status ?? null) !== 'paid') {
                    $viaje->pago->status = 'paid';
                    $viaje->pago->transaction_id = $viaje->pago->transaction_id ?: ('debt_settlement_' . $viaje->id);
                    $viaje->pago->save();
                }
            }

            $resultado['settled'] = true;
            $resultado['pending_debt'] = 0.0;
            
            return $resultado;
        }

        /**
         * Devuelve el saldo actual del usuario autenticado.
         */
        public function getBalance() {
            $usuario = Auth::user();
            
            $saldo = $usuario->wallet_balance ?? 0;
            
            return response()->json([
                'balance' => floatval($saldo),
                'currency' => 'EUR'
            ]);
        }

        /**
         * Historial de transacciones.
         *
         * Actualmente es un stub (devuelve []): se debe conectar a un modelo/tabla
         * real cuando exista.
         */
        public function getTransactions() {
            $usuario = Auth::user();
            
            return response()->json([]);
        }

        /**
         * Resumen de deuda pendiente del usuario.
         */
        public function getDebtSummary() {
            $usuario = Auth::user();
            $deudaPendiente = Deuda::where('user_id', $usuario->id)
                ->where('status', 'pending')
                ->sum('amount');

            return response()->json([
                'pending_debt' => floatval($deudaPendiente),
                'currency' => 'EUR'
            ]);
        }

        /**
         * Añade saldo a la cartera.
         *
         * Nota: tras recargar, intenta liquidar deudas pendientes automáticamente.
         */
        public function addFunds(Request $solicitud) {
            $solicitud->validate([
                'amount' => 'required|numeric|min:5|max:1000'
            ]);

            $usuario = Auth::user();
            $monto = floatval($solicitud->amount);

            $saldoNuevo = null;
            DB::transaction(function () use ($usuario, $monto, &$saldoNuevo) {
                // Se bloquea al usuario para sumar saldo de forma atómica.
                $usuarioBloqueado = User::query()->whereKey($usuario->id)->lockForUpdate()->first();
                if (!$usuarioBloqueado) {
                    throw new \RuntimeException('Usuario no encontrado');
                }

                $saldoActual = (float) ($usuarioBloqueado->wallet_balance ?? 0);
                $usuarioBloqueado->wallet_balance = $saldoActual + $monto;
                $usuarioBloqueado->save();

                // Si había deudas, se intentan liquidar con el saldo recién recargado.
                $this->liquidarDeudasPendientesSiEsPosible($usuarioBloqueado);
                $saldoNuevo = (float) ($usuarioBloqueado->fresh()->wallet_balance ?? 0);
            }, 3);

            return response()->json([
                'success' => true,
                'message' => 'Saldo añadido correctamente',
                'new_balance' => $saldoNuevo,
                // `id` aleatorio: placeholder hasta implementar un sistema real de transacciones.
                'transaction' => [
                    'id' => rand(1000, 9999),
                    'type' => 'credit',
                    'amount' => $monto,
                    'description' => 'Recarga de saldo',
                    'created_at' => now()->toISOString()
                ]
            ]);
        }

        /**
         * Descuenta saldo para pagar un viaje.
         *
         * Nota: este endpoint no marca el viaje como pagado; solo ajusta saldo.
         * La conciliación con `Pago`/estado de viaje debería gestionarse en un flujo único.
         */
        public function useFunds(Request $solicitud) {
            $solicitud->validate([
                'amount' => 'required|numeric|min:0.01',
                'viaje_id' => 'required|integer|exists:viajes,id'
            ]);

            $usuario = Auth::user();
            $monto = floatval($solicitud->amount);
            $saldoActual = $usuario->wallet_balance ?? 0;

            if ($saldoActual < $monto) {

                return response()->json([
                    'success' => false,
                    'message' => 'Saldo insuficiente',
                    'current_balance' => $saldoActual,
                    'required_amount' => $monto
                ], 400);
            }

            $saldoNuevo = $saldoActual - $monto;
            $usuario->wallet_balance = $saldoNuevo;
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Pago procesado correctamente',
                'new_balance' => $saldoNuevo,
                'transaction' => [
                    'id' => rand(1000, 9999),
                    'type' => 'debit',
                    'amount' => $monto,
                    'description' => 'Pago de viaje #' . $solicitud->viaje_id,
                    'created_at' => now()->toISOString()
                ]
            ]);
        }

        /**
         * Solicita retirada de saldo.
         *
         * Actualmente descuenta saldo directamente y devuelve una “transacción” stub.
         * En producción, normalmente se encola una orden de pago y se audita.
         */
        public function withdrawFunds(Request $solicitud) {
            $solicitud->validate([
                'amount' => 'required|numeric|min:5'
            ]);

            $usuario = Auth::user();
            $monto = floatval($solicitud->amount);
            $saldoActual = $usuario->wallet_balance ?? 0;

            if ($saldoActual < $monto) {

                return response()->json([
                    'success' => false,
                    'message' => 'Saldo insuficiente para retirar',
                    'current_balance' => $saldoActual,
                    'requested_amount' => $monto
                ], 400);
            }

            $saldoNuevo = $saldoActual - $monto;
            $usuario->wallet_balance = $saldoNuevo;
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de retirada procesada',
                'new_balance' => $saldoNuevo,
                'transaction' => [
                    'id' => rand(1000, 9999),
                    'type' => 'debit',
                    'amount' => $monto,
                    'description' => 'Retiro de fondos',
                    'created_at' => now()->toISOString()
                ]
            ]);
        }
    }