<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Debt;
    use App\Models\User;
    use App\Models\Viaje;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;

    class WalletController extends Controller {
        private function settlePendingDebtsIfPossible(User $usuario): array
        {
            $result = [
                'had_debt' => false,
                'settled' => false,
                'pending_debt' => 0.0,
            ];

            $debts = Debt::query()
                ->where('user_id', $usuario->id)
                ->where('status', 'pending')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $totalDebt = (float) $debts->sum('amount');
            $result['pending_debt'] = $totalDebt;
            if ($totalDebt <= 0) {
                return $result;
            }

            $result['had_debt'] = true;
            $balance = (float) ($usuario->wallet_balance ?? 0);
            if ($balance < $totalDebt) {
                return $result;
            }

            $usuario->wallet_balance = $balance - $totalDebt;
            $usuario->save();

            foreach ($debts as $debt) {
                $debt->status = 'paid';
                $debt->save();

                if (!$debt->trip_id) {
                    continue;
                }

                $trip = Viaje::query()->with(['conductor', 'pago'])->find($debt->trip_id);
                if (!$trip || !$trip->conductor) {
                    continue;
                }

                $conductorUser = User::query()
                    ->whereKey($trip->conductor->user_id)
                    ->lockForUpdate()
                    ->first();

                if ($conductorUser) {
                    $conductorUser->wallet_balance = (float) ($conductorUser->wallet_balance ?? 0) + (float) ($debt->amount ?? 0);
                    $conductorUser->save();
                }

                if ($trip->pago && ($trip->pago->status ?? null) !== 'paid') {
                    $trip->pago->status = 'paid';
                    $trip->pago->transaction_id = $trip->pago->transaction_id ?: ('debt_settlement_' . $trip->id);
                    $trip->pago->save();
                }
            }

            $result['settled'] = true;
            $result['pending_debt'] = 0.0;
            return $result;
        }

        public function getBalance() {
            $usuario = Auth::user();
            
            $balance = $usuario->wallet_balance ?? 0;
            
            return response()->json([
                'balance' => floatval($balance),
                'currency' => 'EUR'
            ]);
        }

        public function getTransactions() {
            $usuario = Auth::user();
            
            return response()->json([]);
        }

        public function getDebtSummary() {
            $usuario = Auth::user();
            $pendingDebt = Debt::where('user_id', $usuario->id)
                ->where('status', 'pending')
                ->sum('amount');

            return response()->json([
                'pending_debt' => floatval($pendingDebt),
                'currency' => 'EUR'
            ]);
        }

        public function addFunds(Request $solicitud) {
            $solicitud->validate([
                'amount' => 'required|numeric|min:5|max:1000'
            ]);

            $usuario = Auth::user();
            $amount = floatval($solicitud->amount);

            $newBalance = null;
            DB::transaction(function () use ($usuario, $amount, &$newBalance) {
                $lockedUser = User::query()->whereKey($usuario->id)->lockForUpdate()->first();
                if (!$lockedUser) {
                    throw new \RuntimeException('Usuario no encontrado');
                }

                $currentBalance = (float) ($lockedUser->wallet_balance ?? 0);
                $lockedUser->wallet_balance = $currentBalance + $amount;
                $lockedUser->save();

                $this->settlePendingDebtsIfPossible($lockedUser);
                $newBalance = (float) ($lockedUser->fresh()->wallet_balance ?? 0);
            }, 3);

            return response()->json([
                'success' => true,
                'message' => 'Saldo añadido correctamente',
                'new_balance' => $newBalance,
                'transaction' => [
                    'id' => rand(1000, 9999),
                    'type' => 'credit',
                    'amount' => $amount,
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
            $amount = floatval($solicitud->amount);
            $currentBalance = $usuario->wallet_balance ?? 0;

            if ($currentBalance < $amount) {

                return response()->json([
                    'success' => false,
                    'message' => 'Saldo insuficiente',
                    'current_balance' => $currentBalance,
                    'required_amount' => $amount
                ], 400);
            }

            $newBalance = $currentBalance - $amount;
            $usuario->wallet_balance = $newBalance;
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Pago procesado correctamente',
                'new_balance' => $newBalance,
                'transaction' => [
                    'id' => rand(1000, 9999),
                    'type' => 'debit',
                    'amount' => $amount,
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
            $amount = floatval($solicitud->amount);
            $currentBalance = $usuario->wallet_balance ?? 0;

            if ($currentBalance < $amount) {

                return response()->json([
                    'success' => false,
                    'message' => 'Saldo insuficiente para retirar',
                    'current_balance' => $currentBalance,
                    'requested_amount' => $amount
                ], 400);
            }

            $newBalance = $currentBalance - $amount;
            $usuario->wallet_balance = $newBalance;
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de retirada procesada',
                'new_balance' => $newBalance,
                'transaction' => [
                    'id' => rand(1000, 9999),
                    'type' => 'debit',
                    'amount' => $amount,
                    'description' => 'Retiro de fondos',
                    'created_at' => now()->toISOString()
                ]
            ]);
        }
    }