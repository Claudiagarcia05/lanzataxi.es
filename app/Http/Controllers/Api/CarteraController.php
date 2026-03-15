<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Deuda;
    use App\Models\User;
    use App\Models\Viaje;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;

    class CarteraController extends Controller {
        private function liquidarDeudasPendientesSiEsPosible(User $usuario): array
        {
            $resultado = [
                'had_debt' => false,
                'settled' => false,
                'pending_debt' => 0.0,
            ];

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

            $usuario->wallet_balance = $saldo - $deudaTotal;
            $usuario->save();

            foreach ($deudas as $deuda) {
                $deuda->status = 'paid';
                $deuda->save();

                if (!$deuda->trip_id) {
                    continue;
                }

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

        public function getBalance() {
            $usuario = Auth::user();
            
            $saldo = $usuario->wallet_balance ?? 0;
            
            return response()->json([
                'balance' => floatval($saldo),
                'currency' => 'EUR'
            ]);
        }

        public function getTransactions() {
            $usuario = Auth::user();
            
            return response()->json([]);
        }

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

        public function addFunds(Request $solicitud) {
            $solicitud->validate([
                'amount' => 'required|numeric|min:5|max:1000'
            ]);

            $usuario = Auth::user();
            $monto = floatval($solicitud->amount);

            $saldoNuevo = null;
            DB::transaction(function () use ($usuario, $monto, &$saldoNuevo) {
                $usuarioBloqueado = User::query()->whereKey($usuario->id)->lockForUpdate()->first();
                if (!$usuarioBloqueado) {
                    throw new \RuntimeException('Usuario no encontrado');
                }

                $saldoActual = (float) ($usuarioBloqueado->wallet_balance ?? 0);
                $usuarioBloqueado->wallet_balance = $saldoActual + $monto;
                $usuarioBloqueado->save();

                $this->liquidarDeudasPendientesSiEsPosible($usuarioBloqueado);
                $saldoNuevo = (float) ($usuarioBloqueado->fresh()->wallet_balance ?? 0);
            }, 3);

            return response()->json([
                'success' => true,
                'message' => 'Saldo añadido correctamente',
                'new_balance' => $saldoNuevo,
                'transaction' => [
                    'id' => rand(1000, 9999),
                    'type' => 'credit',
                    'amount' => $monto,
                    'description' => 'Recarga de saldo',
                    'created_at' => now()->toISOString()
                ]
            ]);
        }

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