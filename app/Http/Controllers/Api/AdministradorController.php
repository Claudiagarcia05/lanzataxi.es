<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Conductor;
    use App\Models\Taxi;
    use App\Models\Viaje;
    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Hash;

    class AdministradorController extends Controller {
        public function users() {
            $usuarios = User::query()
                ->latest()
                ->get(['id', 'name', 'email', 'role', 'phone', 'is_disabled', 'disabled_at', 'created_at']);

            return response()->json($usuarios);
        }

        public function viajes() {
            $viajes = Viaje::with(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate'])
                ->latest()
                ->get();

            return response()->json($viajes);
        }

        public function stats() {
            $hoy = now()->toDateString();

            return response()->json([
                'totalUsers' => User::count(),
                'activeConductors' => Conductor::where('is_active', true)->count(),
                'totalTaxis' => Taxi::count(),
                'todayTrips' => Viaje::whereDate('created_at', $hoy)->count(),
                'todayRevenue' => (float) Viaje::whereDate('created_at', $hoy)->where('status', 'completed')->sum('price'),
                'averageRating' => round((float) Conductor::avg('rating'), 2),
                'weeklyRevenue' => (float) Viaje::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->where('status', 'completed')->sum('price'),
                'monthlyRevenue' => (float) Viaje::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->where('status', 'completed')->sum('price'),
            ]);
        }

        public function monthlyStats(Request $solicitud) {
            $validado = $solicitud->validate([
                'year' => 'nullable|integer|min:2000|max:2100',
                'month' => 'nullable|integer|min:1|max:12',
            ]);

            $anio = (int) ($validado['year'] ?? now()->year);
            $mes = (int) ($validado['month'] ?? now()->month);

            $desde = Carbon::create($anio, $mes, 1, 0, 0, 0)->startOfMonth();
            $hasta = (clone $desde)->endOfMonth();

            // NOTA:
            // - Viajes completados: usar end_time; si no existe (datos antiguos), fallback a updated_at/created_at.
            // - Viajes cancelados: usar updated_at como aproximación al instante de cancelación.
            $completedDateExpr = DB::raw('COALESCE(end_time, updated_at, created_at)');

            $viajesCompletados = Viaje::query()
                ->where('status', 'completed')
                ->whereBetween($completedDateExpr, [$desde, $hasta])
                ->count();

            $viajesCancelados = Viaje::query()
                ->where('status', 'cancelled')
                ->whereBetween('updated_at', [$desde, $hasta])
                ->count();

            $recaudacion = (float) Viaje::query()
                ->where('status', 'completed')
                ->whereBetween($completedDateExpr, [$desde, $hasta])
                ->sum('price');

            $diasEnMes = (int) $desde->daysInMonth;
            $etiquetas = [];
            $completadosPorDia = array_fill(0, $diasEnMes, 0);
            $canceladosPorDia = array_fill(0, $diasEnMes, 0);
            $recaudacionPorDia = array_fill(0, $diasEnMes, 0.0);

            for ($dia = 1; $dia <= $diasEnMes; $dia++) {
                $etiquetas[] = str_pad((string) $dia, 2, '0', STR_PAD_LEFT);
            }

            $filasCompletados = Viaje::query()
                ->where('status', 'completed')
                ->whereBetween($completedDateExpr, [$desde, $hasta])
                ->selectRaw('DATE(COALESCE(end_time, updated_at, created_at)) as day, COUNT(*) as cnt, COALESCE(SUM(price), 0) as rev')
                ->groupBy('day')
                ->get();

            foreach ($filasCompletados as $fila) {
                $dia = $fila->day ? Carbon::parse($fila->day)->day : null;
                if (!$dia || $dia < 1 || $dia > $diasEnMes) continue;
                $indice = $dia - 1;
                $completadosPorDia[$indice] = (int) ($fila->cnt ?? 0);
                $recaudacionPorDia[$indice] = (float) ($fila->rev ?? 0);
            }

            $filasCancelados = Viaje::query()
                ->where('status', 'cancelled')
                ->whereBetween('updated_at', [$desde, $hasta])
                ->selectRaw('DATE(updated_at) as day, COUNT(*) as cnt')
                ->groupBy('day')
                ->get();

            foreach ($filasCancelados as $fila) {
                $dia = $fila->day ? Carbon::parse($fila->day)->day : null;
                if (!$dia || $dia < 1 || $dia > $diasEnMes) continue;
                $indice = $dia - 1;
                $canceladosPorDia[$indice] = (int) ($fila->cnt ?? 0);
            }

            $fechaMinima = Viaje::min('created_at');
            $fechaMaxima = Viaje::max('created_at');
            $anioMinimo = $fechaMinima ? Carbon::parse($fechaMinima)->year : now()->year;
            $anioMaximo = $fechaMaxima ? Carbon::parse($fechaMaxima)->year : now()->year;

            return response()->json([
                'year' => $anio,
                'month' => $mes,
                'completedTrips' => $viajesCompletados,
                'cancelledTrips' => $viajesCancelados,
                'revenue' => $recaudacion,
                'minYear' => $anioMinimo,
                'maxYear' => $anioMaximo,
                'daily' => [
                    'labels' => $etiquetas,
                    'completedTrips' => $completadosPorDia,
                    'cancelledTrips' => $canceladosPorDia,
                    'revenue' => $recaudacionPorDia,
                ],
            ]);
        }

        public function pendingconductors() {
            $pendiente = Conductor::query()
                ->with([
                    'user:id,name,email,phone,is_disabled',
                    'taxi:id,conductor_id,plate,model,capacity,color,status',
                ])
                ->where('approval_status', 'pending')
                ->latest()
                ->get();

            return response()->json($pendiente);
        }

        public function approveConductor(Conductor $conductor)
        {
            $conductor->approval_status = 'approved';
            $conductor->approved_at = now();
            $conductor->rejected_at = null;
            $conductor->save();

            return response()->json($conductor->fresh(['user:id,name,email,phone,is_disabled', 'taxi']));
        }

        public function rejectConductor(Conductor $conductor)
        {
            $conductor->approval_status = 'rejected';
            $conductor->rejected_at = now();
            $conductor->approved_at = null;
            $conductor->is_active = false;
            $conductor->save();

            $conductor->loadMissing('taxi');
            if ($conductor->taxi && $conductor->taxi->status !== 'offline') {
                $conductor->taxi->update(['status' => 'offline']);
            }

            return response()->json($conductor->fresh(['user:id,name,email,phone,is_disabled', 'taxi']));
        }

        public function disableUser(User $usuario)
        {
            $usuario->is_disabled = true;
            if (empty($usuario->disabled_at)) {
                $usuario->disabled_at = now();
            }
            $usuario->save();

            if (($usuario->role ?? null) === 'conductor') {
                $usuario->loadMissing('conductor.taxi');
                if ($usuario->conductor) {
                    $usuario->conductor->is_active = false;
                    $usuario->conductor->save();
                    if ($usuario->conductor->taxi && $usuario->conductor->taxi->status !== 'offline') {
                        $usuario->conductor->taxi->update(['status' => 'offline']);
                    }
                }
            }

            return response()->json($usuario->fresh(['conductor']));
        }

        public function conductorEarningsReport(Conductor $conductor)
        {
            $filas = Viaje::query()
                ->where('conductor_id', $conductor->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->select(['id', 'status', 'price', 'created_at'])
                ->orderBy('created_at')
                ->get();

            $agrupado = [];
            $totales = [
                'completedTrips' => 0,
                'cancelledTrips' => 0,
                'revenue' => 0.0,
            ];

            foreach ($filas as $fila) {
                $clave = Carbon::parse($fila->created_at)->format('Y-m');
                if (!isset($agrupado[$clave])) {
                    $agrupado[$clave] = [
                        'month' => $clave,
                        'completedTrips' => 0,
                        'cancelledTrips' => 0,
                        'revenue' => 0.0,
                    ];
                }

                if ($fila->status === 'completed') {
                    $agrupado[$clave]['completedTrips']++;
                    $agrupado[$clave]['revenue'] += (float) ($fila->price ?? 0);
                    $totales['completedTrips']++;
                    $totales['revenue'] += (float) ($fila->price ?? 0);
                } elseif ($fila->status === 'cancelled') {
                    $agrupado[$clave]['cancelledTrips']++;
                    $totales['cancelledTrips']++;
                }
            }

            $meses = array_values($agrupado);
            usort($meses, fn ($a, $b) => strcmp($a['month'], $b['month']));

            $conductor->loadMissing('user:id,name,email,phone');

            return response()->json([
                'conductor' => [
                    'id' => $conductor->id,
                    'name' => $conductor->user?->name,
                    'email' => $conductor->user?->email,
                    'phone' => $conductor->user?->phone,
                ],
                'totals' => $totales,
                'months' => $meses,
            ]);
        }

        public function clientTripsReport(User $usuario)
        {
            // UX: desde el panel se puede intentar generar informe aunque el usuario no tenga
            // viajes (o incluso si su rol no es pasajero). En vez de 400, devolvemos un informe
            // vacío para evitar errores en frontend.
            $esPasajero = (($usuario->role ?? null) === 'pasajero');

            $viajes = $esPasajero
                ? Viaje::query()
                    ->where('pasajero_id', $usuario->id)
                    ->select(['id', 'status', 'price', 'pickup_address', 'dropoff_address', 'created_at'])
                    ->latest()
                    ->limit(200)
                    ->get()
                : collect();

            return response()->json([
                'client' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'phone' => $usuario->phone,
                ],
                'trips' => $viajes,
                'note' => $esPasajero ? null : 'El usuario no es pasajero; informe sin viajes.',
            ]);
        }

        public function createAdmin(Request $solicitud)
        {
            $validado = $solicitud->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email:rfc,dns|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'phone' => 'nullable|string|max:50',
            ], [
                'email.unique' => 'El email ya está registrado.',
            ]);

            $correo = strtolower(trim($validado['email'] ?? ''));
            if (!str_ends_with($correo, '@admin.es')) {
                return response()->json([
                    'message' => 'Validación fallida.',
                    'errors' => [
                        'email' => ['El email del administrador debe terminar en @admin.es.'],
                    ],
                ], 422);
            }

            $usuario = User::create([
                'name' => $validado['name'],
                'email' => $validado['email'],
                'password' => Hash::make($validado['password']),
                'role' => 'admin',
                'phone' => $validado['phone'] ?? null,
                'wallet_balance' => 0,
                'is_disabled' => false,
            ]);

            return response()->json([
                'message' => 'Administrador creado correctamente.',
                'user' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'role' => $usuario->role,
                    'phone' => $usuario->phone,
                ],
            ], 201);
        }
    }