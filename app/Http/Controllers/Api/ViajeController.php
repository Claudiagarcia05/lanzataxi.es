<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Viaje;
    use App\Models\Deuda;
    use App\Models\Pago;
    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;

    class ViajeController extends Controller {
        private function calcularPrecio($origen, $destino, $distance, $hora = null) {
            $municipios = [
                'Arrecife', 'Puerto del Carmen', 'Costa Teguise', 'Playa Blanca', 'Haria', 'Teguise', 'Aeropuerto', 'Puerto Calero'
            ];

            $trayectosFijos = [
                ['Aeropuerto', 'Arrecife', 10, 14],
                ['Aeropuerto', 'Puerto del Carmen', 12, 18],
                ['Aeropuerto', 'Costa Teguise', 20, 24],
                ['Arrecife', 'Playa Blanca', 45, 50],
                ['Puerto Calero', 'Aeropuerto', 45.86, null],
            ];

            $isNoche = false;
            if ($hora) {
                $h = is_string($hora) ? date('H', strtotime($hora)) : $hora->format('H');
                $isNoche = ($h >= 22 || $h < 6);
            }

            $origen = ucfirst(strtolower($origen));
            $destino = ucfirst(strtolower($destino));

            foreach ($trayectosFijos as $t) {
                if ((($t[0] === $origen && $t[1] === $destino) || ($t[1] === $origen && $t[0] === $destino))) {
                    if ($isNoche && $t[3]) return $t[3];

                    return $t[2];
                }
            }

            if ($origen === 'Arrecife' && $destino === 'Arrecife') {
                $bajada = $isNoche ? 3.65 : 3.05;
                $precioKm = $isNoche ? 0.92 : 0.80;

                return round($bajada + ($distance * $precioKm), 2);
            }

            if ($origen === 'Arrecife' || $destino === 'Arrecife') {
                $bajada = $isNoche ? 3.65 : 3.05;
                $precioKm = $isNoche ? 0.92 : 0.80;

                return round($bajada + ($distance * $precioKm), 2);
            }

            $bajada = 3.50;
            $precioKm = 1.10;

            return round($bajada + ($distance * $precioKm), 2);
        }
        public function userviajes(Request $solicitud) {
            $usuario = $solicitud->user();
            $viajes = $usuario->viajesAspasajero()
                ->with([
                    'pasajero:id,name,email',
                    'conductor.user:id,name',
                    'taxi:id,plate,model',
                    'pago'
                ])
                ->latest()
                ->get();

            return response()->json($viajes);
        }

        public function driverTrips(Request $solicitud) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor) {

                return response()->json(['message' => 'conductor profile not found'], 404);
            }

            $viajes = $conductor->viajes()
                ->with(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate,model', 'pago'])
                ->latest()
                ->get();

            return response()->json($viajes);
        }


        public function available(Request $solicitud) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor) {

                return response()->json(['message' => 'conductor profile not found'], 404);
            }

            if (!$conductor->is_active) {

                return response()->json([]);
            }

            $taxi = $conductor->ensureTaxiExists('available');
            $capacity = (int) ($taxi->capacity ?? 4);
            if ($capacity <= 0) {
                $capacity = 4;
            }

            $viajes = Viaje::query()
                ->where('status', 'pending')
                ->whereNull('conductor_id')
                ->where('pasajeros', '<=', $capacity)
                ->with([
                    'pasajero:id,name',
                    'conductor.user:id,name',
                    'taxi:id,plate,model',
                    'pago',
                ])
                ->latest()
                ->get();

            return response()->json($viajes);
        }

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

                $viaje = Viaje::query()->with('conductor')->find($deuda->trip_id);
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

                $viaje->loadMissing('pago');
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

        public function store(Request $solicitud) {
            $validado = $solicitud->validate([
                // Lanzarote (aprox.): lat 28.85..29.35, lng -13.95..-13.20
                'pickup_lat' => 'required|numeric|between:28.85,29.35',
                'pickup_lng' => 'required|numeric|between:-13.95,-13.20',
                'dropoff_lat' => 'required|numeric|between:28.85,29.35',
                'dropoff_lng' => 'required|numeric|between:-13.95,-13.20',
                'pickup_address' => 'nullable|string|max:255',
                'dropoff_address' => 'nullable|string|max:255',
                'distance' => 'nullable|numeric|min:0',
                'scheduled_for' => 'nullable|date_format:Y-m-d H:i',
                'pasajeros' => 'nullable|integer|min:1|max:6',
                'luggage' => 'nullable|integer|min:0|max:10',
                'pago_method' => 'nullable|string|in:efectivo,wallet,tarjeta',
                'notes' => 'nullable|string|max:1000',
            ], [
                'pickup_lat.between' => 'La ubicación de origen debe estar dentro de Lanzarote.',
                'pickup_lng.between' => 'La ubicación de origen debe estar dentro de Lanzarote.',
                'dropoff_lat.between' => 'La ubicación de destino debe estar dentro de Lanzarote.',
                'dropoff_lng.between' => 'La ubicación de destino debe estar dentro de Lanzarote.',
            ]);

            $municipios = [
                'Arrecife', 'Puerto del Carmen', 'Costa Teguise', 'Playa Blanca', 'Haria', 'Teguise', 'Aeropuerto', 'Puerto Calero'
            ];

            $origen = $validado['pickup_address'] ?? 'Arrecife';
            $destino = $validado['dropoff_address'] ?? 'Arrecife';
            $distancia = $validado['distance'] ?? 5.5;
            $hora = $validado['scheduled_for'] ?? now();

            $getMunicipio = function($direccion) use ($municipios) {
                foreach ($municipios as $m) {
                    if (stripos($direccion, $m) !== false) return $m;
                }

                return 'Arrecife';
            };
            $mun_origen = $getMunicipio($origen);
            $mun_destino = $getMunicipio($destino);

            $precio = $this->calcularPrecio($mun_origen, $mun_destino, $distancia, $hora);

            $usuario = $solicitud->user();

            $existeViajeActivo = Viaje::query()
                ->where('pasajero_id', $usuario->id)
                ->whereIn('status', ['pending', 'accepted', 'in_progress'])
                ->exists();

            if ($existeViajeActivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes pedir un taxi nuevo mientras tengas un viaje pendiente, aceptado o en curso.'
                ], 409);
            }

            try {
                $chequeoDeuda = DB::transaction(function () use ($usuario) {
                    $usuarioBloqueado = User::query()->whereKey($usuario->id)->lockForUpdate()->first();
                    if (!$usuarioBloqueado) {
                        throw new \RuntimeException('Usuario no encontrado');
                    }
                    return $this->liquidarDeudasPendientesSiEsPosible($usuarioBloqueado);
                }, 3);
            } catch (\RuntimeException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }

            // Refrescar saldo por si se liquidaron deudas
            $usuario = $usuario->fresh();

            if (!empty($chequeoDeuda['had_debt']) && empty($chequeoDeuda['settled'])) {
                $pendiente = (float) ($chequeoDeuda['pending_debt'] ?? 0);
                return response()->json([
                    'success' => false,
                    'message' => 'Tienes una deuda pendiente de ' . number_format($pendiente, 2, '.', '') . '€ en tu cartera virtual. Añade saldo para poder solicitar un nuevo taxi.',
                    'pending_debt' => $pendiente,
                ], 400);
            }

            if ($this->mapPaymentMethod($validado['pago_method'] ?? 'efectivo') === 'app') {
                $saldo = $usuario->wallet_balance ?? 0;
                if ($saldo < $precio) {

                    return response()->json([
                        'success' => false,
                        'message' => 'Saldo insuficiente en la cartera virtual. Añade dinero o elige otro método de pago.',
                        'current_balance' => $saldo,
                        'required_amount' => $precio
                    ], 400);
                }
            }

            $viaje = Viaje::create([
                'pasajero_id' => $solicitud->user()->id,
                'pickup_lat' => $validado['pickup_lat'],
                'pickup_lng' => $validado['pickup_lng'],
                'dropoff_lat' => $validado['dropoff_lat'],
                'dropoff_lng' => $validado['dropoff_lng'],
                'pickup_address' => $validado['pickup_address'] ?? null,
                'dropoff_address' => $validado['dropoff_address'] ?? null,
                'scheduled_for' => $validado['scheduled_for'] ?? null,
                'pasajeros' => $validado['pasajeros'] ?? 1,
                'luggage' => $validado['luggage'] ?? 0,
                'pago_method' => $this->mapPaymentMethod($validado['pago_method'] ?? 'efectivo'),
                'notes' => $validado['notes'] ?? null,
                'status' => 'pending',
                'distance' => $distancia,
                'price' => $precio,
            ]);

            $viaje->co2_saved = $viaje->calculateCO2Saved();
            $viaje->save();

            return response()->json($viaje->load(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate,model', 'pago']), 201);
        }

        public function show(Viaje $viaje) {

            return response()->json($viaje->load(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate,model', 'pago']));
        }

        public function cancel(Viaje $viaje) {
            if ($viaje->pasajero_id !== auth()->id()) {
                
                return response()->json(['message' => 'No autorizado'], 403);
            }

            if (!in_array($viaje->status, ['pending', 'accepted'], true)) {

                return response()->json(['message' => 'viaje cannot be cancelled'], 400);
            }

            try {
                $viajeActualizado = DB::transaction(function () use ($viaje) {
                    $viajeBloqueado = Viaje::query()->whereKey($viaje->id)->lockForUpdate()->first();
                    if (!$viajeBloqueado) {
                        return null;
                    }

                    if (!in_array($viajeBloqueado->status, ['pending', 'accepted'], true)) {
                        throw new \RuntimeException('viaje cannot be cancelled');
                    }

                    $usuarioPasajero = User::query()->whereKey($viajeBloqueado->pasajero_id)->lockForUpdate()->first();
                    if (!$usuarioPasajero) {
                        throw new \RuntimeException('Usuario no encontrado');
                    }

                    $precioViaje = (float) ($viajeBloqueado->price ?? 0);
                    $saldoActual = (float) ($usuarioPasajero->wallet_balance ?? 0);
                    $cobradoAhora = min($saldoActual, $precioViaje);
                    $deudaRestante = max(0, $precioViaje - $cobradoAhora);

                    // Cambiar estado
                    $viajeBloqueado->status = 'cancelled';
                    $viajeBloqueado->save();

                    // Cobro inmediato desde cartera (si hay saldo)
                    $usuarioPasajero->wallet_balance = $saldoActual - $cobradoAhora;
                    $usuarioPasajero->save();

                    // Pagar al conductor (si existe) con lo cobrado ahora
                    if ($cobradoAhora > 0 && $viajeBloqueado->conductor_id) {
                        $viajeBloqueado->loadMissing('conductor');
                        if ($viajeBloqueado->conductor) {
                            $usuarioConductor = User::query()
                                ->whereKey($viajeBloqueado->conductor->user_id)
                                ->lockForUpdate()
                                ->first();
                            if ($usuarioConductor) {
                                $usuarioConductor->wallet_balance = (float) ($usuarioConductor->wallet_balance ?? 0) + $cobradoAhora;
                                $usuarioConductor->save();
                            }
                        }
                    }

                    // Si falta dinero, crear deuda pendiente (se liquidará al recargar o antes del siguiente viaje)
                    if ($deudaRestante > 0) {
                        Deuda::create([
                            'user_id' => $usuarioPasajero->id,
                            'trip_id' => $viajeBloqueado->id,
                            'amount' => $deudaRestante,
                            'status' => 'pending',
                            'reason' => 'Cancelación de viaje - Cobro total'
                        ]);
                    }

                    // Registrar pago del viaje cancelado (paid si cobrado completo, pending si queda deuda)
                    $viajeBloqueado->loadMissing('pago');
                    if (!$viajeBloqueado->pago) {
                        $viajeBloqueado->pago()->create([
                            'amount' => $precioViaje,
                            'method' => 'app',
                            'status' => $deudaRestante > 0 ? 'pending' : 'paid',
                            'transaction_id' => 'cancel_wallet_' . $viajeBloqueado->id,
                        ]);
                    } else {
                        if ($deudaRestante <= 0 && ($viajeBloqueado->pago->status ?? null) !== 'paid') {
                            $viajeBloqueado->pago->status = 'paid';
                            $viajeBloqueado->pago->save();
                        }
                    }

                    return $viajeBloqueado;
                }, 3);
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 400);
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['message' => 'Error al cancelar el viaje'], 500);
            }

            if ($viajeActualizado === null) {
                return response()->json(['message' => 'viaje not found'], 404);
            }

            return response()->json($viajeActualizado->fresh(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate,model', 'pago']));
        }

        public function track(Viaje $viaje) {
            if (!$viaje->conductor) {

                return response()->json(['message' => 'conductor not assigned'], 404);
            }

            $ubicacion = $viaje->conductor->ubicacions()->latest()->first();

            if (!$ubicacion) {

                return response()->json(['message' => 'ubicacion not available'], 404);
            }

            return response()->json([
                'ubicacion' => [
                    'lat' => (float) $ubicacion->lat,
                    'lng' => (float) $ubicacion->lng,
                    'updated_at' => $ubicacion->updated_at,
                ],
            ]);
        }

        public function accept(Request $solicitud, Viaje $viaje) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor) {

                return response()->json(['message' => 'conductor profile not found'], 404);
            }

            if (!$conductor->is_active) {

                return response()->json(['message' => 'conductor not active'], 403);
            }

            try {
                $viajeActualizado = DB::transaction(function () use ($viaje, $conductor) {
                    $locked = Viaje::query()
                        ->whereKey($viaje->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$locked) {
                        return null;
                    }

                    if ($locked->status !== 'pending' || $locked->conductor_id !== null) {
                        return false;
                    }

                    $taxiId = $conductor->taxi?->id;
                    if (!$taxiId) {
                        $taxiId = $conductor->ensureTaxiExists('available')->id;
                    }

                    $locked->update([
                        'conductor_id' => $conductor->id,
                        'taxi_id' => $taxiId,
                        'status' => 'accepted',
                    ]);

                    // Si el método es cartera virtual (app), el pago se realiza al aceptar.
                    if (($locked->pago_method ?? null) === 'app') {
                        // Evitar doble cobro si por cualquier razón ya existiera un pago.
                        $locked->loadMissing('pago');
                        if (!$locked->pago) {
                            $amount = (float) ($locked->price ?? 0);

                            // Bloquear y ajustar saldos de pasajero y conductor (usuario) de forma atómica.
                            $pasajero = User::query()->whereKey($locked->pasajero_id)->lockForUpdate()->first();
                            $conductorUser = User::query()->whereKey($conductor->user_id)->lockForUpdate()->first();

                            if (!$pasajero || !$conductorUser) {
                                throw new \RuntimeException('Usuarios no disponibles para procesar el pago');
                            }

                            $saldo = (float) ($pasajero->wallet_balance ?? 0);
                            if ($saldo < $amount) {
                                // Lanzar para forzar rollback (el viaje no se asigna si no se puede cobrar).
                                throw new \RuntimeException('Saldo insuficiente en la cartera virtual');
                            }

                            $pasajero->wallet_balance = $saldo - $amount;
                            $pasajero->save();

                            $conductorSaldo = (float) ($conductorUser->wallet_balance ?? 0);
                            $conductorUser->wallet_balance = $conductorSaldo + $amount;
                            $conductorUser->save();

                            $locked->pago()->create([
                                'amount' => $amount,
                                'method' => 'app',
                                'status' => 'paid',
                                'transaction_id' => 'wallet_' . $locked->id,
                            ]);
                        }
                    }

                    return $locked;
                }, 3);
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 400);
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['message' => 'Error al aceptar el viaje'], 500);
            }

            if ($viajeActualizado === null) {
                return response()->json(['message' => 'viaje not found'], 404);
            }

            if ($viajeActualizado === false) {
                return response()->json(['message' => 'viaje already accepted'], 409);
            }

            return response()->json($viajeActualizado->load(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate,model', 'pago']));
        }

        public function start(Request $solicitud, Viaje $viaje) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor || (int) $viaje->conductor_id !== (int) $conductor->id) {

                return response()->json(['message' => 'No autorizado'], 403);
            }

            if ($viaje->status !== 'accepted') {

                return response()->json(['message' => 'viaje cannot be started'], 400);
            }

            $viaje->update(['status' => 'in_progress']);

            return response()->json($viaje->fresh(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate,model', 'pago']));
        }

        public function complete(Request $solicitud, Viaje $viaje) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor || (int) $viaje->conductor_id !== (int) $conductor->id) {

                return response()->json(['message' => 'No autorizado'], 403);
            }

            if ($viaje->status !== 'in_progress') {

                return response()->json(['message' => 'viaje cannot be completed'], 400);
            }

            $validado = $solicitud->validate([
                'rating' => 'nullable|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $viaje->update([
                'status' => 'completed',
                'rating' => $validado['rating'] ?? $viaje->rating,
                'comment' => $validado['comment'] ?? $viaje->comment,
                'end_time' => now(),
            ]);

            // Si el pago es en efectivo o tarjeta, al completar lo marcamos como pagado.
            // En cartera virtual, el pago ya se creó al aceptar (pero dejamos un fallback por consistencia).
            $viaje->loadMissing('pago');
            if (!$viaje->pago) {
                $method = $viaje->pago_method ?? 'cash';
                if (in_array($method, ['cash', 'card', 'app'], true)) {
                    $viaje->pago()->create([
                        'amount' => (float) ($viaje->price ?? 0),
                        'method' => $method,
                        'status' => 'paid',
                        'transaction_id' => $method === 'app' ? ('wallet_' . $viaje->id) : null,
                    ]);
                }
            }

            return response()->json($viaje->fresh(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate,model', 'pago']));
        }

        public function reports() {
            $datos = [
                'total' => Viaje::count(),
                'completed' => Viaje::where('status', 'completed')->count(),
                'cancelled' => Viaje::where('status', 'cancelled')->count(),
                'revenue' => Viaje::where('status', 'completed')->sum('price'),
            ];

            return response()->json($datos);
        }

        public function rate(Request $solicitud, Viaje $viaje) {
            if ($viaje->pasajero_id !== $solicitud->user()->id) {

                return response()->json(['message' => 'No autorizado'], 403);
            }

            if ($viaje->status !== 'completed') {

                return response()->json(['message' => 'Solo puedes valorar viajes completados'], 400);
            }

            $validado = $solicitud->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $viaje->update([
                'rating' => $validado['rating'],
                'comment' => $validado['comment'] ?? null,
            ]);

            return response()->json($viaje->fresh(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate,model', 'pago']));
        }

        private function mapPaymentMethod($metodo) {
            $mapa = [
                'efectivo' => 'cash',
                'wallet' => 'app',
                'tarjeta' => 'card',
                'cash' => 'cash',
                'card' => 'card',
                'app' => 'app',
            ];

            return $mapa[$metodo] ?? 'cash';
        }
    }