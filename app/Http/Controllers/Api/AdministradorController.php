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
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Database\QueryException;

    /**
     * Endpoints de administración.
     *
     * Nota: varias respuestas intentan usar columnas opcionales (p.ej. `is_disabled`)
     * y hacen fallback si la BD aún no está migrada. Esto permite desplegar en
     * entornos donde el esquema todavía no coincide al 100%.
     */
    class AdministradorController extends Controller {
        /**
         * Lista usuarios (campos preferidos si existen en el esquema).
         */
        public function users() {
            $preferidas = ['id', 'name', 'email', 'role', 'phone', 'is_disabled', 'disabled_at', 'created_at'];

            try {
                $usuarios = User::query()
                    ->latest()
                    ->get($preferidas);
            } catch (QueryException $e) {
                $usuarios = User::query()
                    ->latest()
                    ->get(['id', 'name', 'email', 'created_at']);
            }

            return response()->json($usuarios);
        }

        /**
         * Lista viajes con relaciones mínimas para panel/admin.
         */
        public function viajes() {
            $viajes = Viaje::with(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate'])
                ->latest()
                ->get();

            return response()->json($viajes);
        }

        /**
         * Métricas rápidas (hoy/semana/mes).
         *
         * `todayRevenue/weeklyRevenue/monthlyRevenue` suma:
         * - Viajes completados.
         * - Viajes cancelados con conductor asignado (cobros por cancelación).
         */
        public function stats() {
            $hoyInicio = now()->startOfDay();
            $hoyFin = now()->endOfDay();
            $completedDateExpr = DB::raw('COALESCE(end_time, updated_at, created_at)');

            return response()->json([
                'totalUsers' => User::count(),
                'activeConductors' => Conductor::where('is_active', true)->count(),
                'totalTaxis' => Taxi::count(),
                'todayTrips' => Viaje::whereBetween('created_at', [$hoyInicio, $hoyFin])->count(),
                'todayRevenue' => (float) (
                    (float) Viaje::where('status', 'completed')
                        ->whereBetween($completedDateExpr, [$hoyInicio, $hoyFin])
                        ->sum('price')
                    + (float) Viaje::where('status', 'cancelled')
                        ->whereNotNull('conductor_id')
                        ->whereBetween('updated_at', [$hoyInicio, $hoyFin])
                        ->sum('price')
                ),
                'averageRating' => round((float) Conductor::avg('rating'), 2),
                'weeklyRevenue' => (float) (
                    (float) Viaje::where('status', 'completed')
                        ->whereBetween($completedDateExpr, [now()->startOfWeek(), now()->endOfWeek()])
                        ->sum('price')
                    + (float) Viaje::where('status', 'cancelled')
                        ->whereNotNull('conductor_id')
                        ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                        ->sum('price')
                ),
                'monthlyRevenue' => (float) (
                    (float) Viaje::where('status', 'completed')
                        ->whereBetween($completedDateExpr, [now()->startOfMonth(), now()->endOfMonth()])
                        ->sum('price')
                    + (float) Viaje::where('status', 'cancelled')
                        ->whereNotNull('conductor_id')
                        ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
                        ->sum('price')
                ),
            ]);
        }

        /**
         * Métricas diarias de un mes concreto para gráficas.
         *
         * Acepta `year` y `month` opcionales; por defecto usa el mes actual.
         */
        public function monthlyStats(Request $solicitud) {
            $validado = $solicitud->validate([
                'year' => 'nullable|integer|min:2000|max:2100',
                'month' => 'nullable|integer|min:1|max:12',
            ]);

            $anio = (int) ($validado['year'] ?? now()->year);
            $mes = (int) ($validado['month'] ?? now()->month);

            $desde = Carbon::create($anio, $mes, 1, 0, 0, 0)->startOfMonth();
            $hasta = (clone $desde)->endOfMonth();

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

            $recaudacionCancelaciones = (float) Viaje::query()
                ->where('status', 'cancelled')
                ->whereNotNull('conductor_id')
                ->whereBetween('updated_at', [$desde, $hasta])
                ->sum('price');

            $recaudacion = (float) ($recaudacion + $recaudacionCancelaciones);

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

            $filasRecaudacionCancelados = Viaje::query()
                ->where('status', 'cancelled')
                ->whereNotNull('conductor_id')
                ->whereBetween('updated_at', [$desde, $hasta])
                ->selectRaw('DATE(updated_at) as day, COALESCE(SUM(price), 0) as rev')
                ->groupBy('day')
                ->get();

            foreach ($filasRecaudacionCancelados as $fila) {
                $dia = $fila->day ? Carbon::parse($fila->day)->day : null;
                if (!$dia || $dia < 1 || $dia > $diasEnMes) continue;
                $indice = $dia - 1;
                $recaudacionPorDia[$indice] += (float) ($fila->rev ?? 0);
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

        /**
         * Devuelve conductores pendientes de aprobación.
         */
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

        /**
         * Aprueba a un conductor (habilita su operación en el sistema).
         */
        public function approveConductor(Conductor $conductor) {
            $conductor->approval_status = 'approved';
            $conductor->approved_at = now();
            $conductor->rejected_at = null;
            $conductor->save();

            return response()->json($conductor->fresh(['user:id,name,email,phone,is_disabled', 'taxi']));
        }

        /**
         * Rechaza a un conductor y fuerza su taxi a `offline`.
         */
        public function rejectConductor(Conductor $conductor) {
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

        /**
         * Desactiva un usuario.
         *
         * Implementación defensiva: usa `DB::table()` para poder funcionar incluso
         * si el modelo/atributos cambian entre despliegues y captura errores de
         * esquema (columnas aún no migradas).
         */
        public function disableUser(User $user) {
            try {
                $filas = DB::table('users')
                    ->where('id', $user->id)
                    ->update(['is_disabled' => true]);

                try {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->whereNull('disabled_at')
                        ->update(['disabled_at' => now()]);
                } catch (QueryException $e) {

                }
            } catch (QueryException $e) {
                $mensaje = (string) ($e->getMessage() ?? '');
                $esColumnaFaltante = str_contains($mensaje, "Unknown column 'is_disabled'")
                    || str_contains($mensaje, 'Column not found')
                    || str_contains($mensaje, '42S22');

                if ($esColumnaFaltante) {
                    // Indica un desajuste de esquema: la app espera columnas que aún no existen.
                    return response()->json([
                        'message' => 'La base de datos no está actualizada (faltan columnas en users). Ejecuta las migraciones en el servidor: php artisan migrate --force',
                    ], 409);
                }

                throw $e;
            }

            try {
                $user->tokens()->delete();
            } catch (\Throwable $e) {

            }

            $valorIsDisabled = null;
            try {
                $valorIsDisabled = DB::table('users')->where('id', $user->id)->value('is_disabled');
            } catch (QueryException $e) {

                return response()->json([
                    'message' => 'La base de datos no está actualizada (faltan columnas en users). Ejecuta las migraciones en el servidor: php artisan migrate --force',
                ], 409);
            }

            $isDisabledAhora = (bool) $valorIsDisabled;

            if (!$isDisabledAhora) {
                // Si el update no persistió, devolvemos conflicto para que el panel/admin lo detecte.
                \Log::warning('No se pudo persistir is_disabled al dar de baja', [
                    'user_id' => $user->id,
                    'updated_rows' => $filas ?? null,
                    'db_value' => $valorIsDisabled,
                ]);

                return response()->json([
                    'message' => 'No se pudo dar de baja al usuario (el cambio no se guardó). Revisa la configuración de BD y permisos.',
                ], 409);
            }

            if (($user->role ?? null) === 'conductor') {
                // Si el usuario era conductor, lo forzamos a offline para evitar que siga disponible.
                $user->loadMissing('conductor.taxi');
                if ($user->conductor) {
                    $user->conductor->is_active = false;
                    $user->conductor->save();
                    if ($user->conductor->taxi && $user->conductor->taxi->status !== 'offline') {
                        $user->conductor->taxi->update(['status' => 'offline']);
                    }
                }
            }

            return response()->json($user->refresh()->loadMissing('conductor'));
        }

        /**
         * Informe de ganancias de un conductor (agregado por mes + listado de viajes).
         */
        public function conductorEarningsReport(Conductor $conductor) {
            $filas = Viaje::query()
                ->where('conductor_id', $conductor->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->select(['id', 'status', 'price', 'pickup_address', 'dropoff_address', 'created_at', 'updated_at'])
                ->orderByDesc('created_at')
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

                    $agrupado[$clave]['revenue'] += (float) ($fila->price ?? 0);
                    $totales['revenue'] += (float) ($fila->price ?? 0);
                }
            }

            $meses = array_values($agrupado);
            usort($meses, fn ($a, $b) => strcmp($a['month'], $b['month']));

            $conductor->loadMissing('user:id,name,email,phone');

            $trips = $filas->map(function ($fila) {
                $ganancia = in_array($fila->status, ['completed', 'cancelled'], true)
                    ? (float) ($fila->price ?? 0)
                    : 0.0;

                return [
                    'id' => $fila->id,
                    'status' => $fila->status,
                    'price' => (float) ($fila->price ?? 0),
                    'earnings' => $ganancia,
                    'pickup_address' => $fila->pickup_address,
                    'dropoff_address' => $fila->dropoff_address,
                    'created_at' => $fila->created_at,
                    'updated_at' => $fila->updated_at,
                ];
            })->values();

            return response()->json([
                'conductor' => [
                    'id' => $conductor->id,
                    'name' => $conductor->user?->name,
                    'email' => $conductor->user?->email,
                    'phone' => $conductor->user?->phone,
                ],
                'totals' => $totales,
                'months' => $meses,
                'trips' => $trips,
            ]);
        }

        /**
         * Informe de viajes de un cliente (solo si su rol es `pasajero`).
         */
        public function clientTripsReport(User $user) {
            $esPasajero = (($user->role ?? null) === 'pasajero');

            $viajes = $esPasajero
                ? Viaje::query()
                    ->where('pasajero_id', $user->id)
                    ->select(['id', 'status', 'price', 'pickup_address', 'dropoff_address', 'created_at'])
                    ->latest()
                    ->limit(200)
                    ->get()
                : collect();

            return response()->json([
                'client' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'trips' => $viajes,
                'note' => $esPasajero ? null : 'El usuario no es pasajero; informe sin viajes.',
            ]);
        }

        /**
         * Crea un usuario administrador.
         *
         * Regla adicional: el email debe terminar en `@admin.es`.
         */
        public function createAdmin(Request $solicitud) {
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