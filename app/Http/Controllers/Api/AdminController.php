<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Conductor;
    use App\Models\Taxi;
    use App\Models\Viaje;
    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Carbon;

    class AdminController extends Controller {
        public function users() {
            $usuarios = User::query()
                ->latest()
                ->get(['id', 'name', 'email', 'role', 'phone', 'is_disabled', 'created_at']);

            return response()->json($usuarios);
        }

        public function viajes() {
            $viajes = Viaje::with(['pasajero:id,name', 'conductor.user:id,name', 'taxi:id,plate'])
                ->latest()
                ->get();

            return response()->json($viajes);
        }

        public function stats() {
            $today = now()->toDateString();

            return response()->json([
                'totalUsers' => User::count(),
                'activeConductors' => Conductor::where('is_active', true)->count(),
                'totalTaxis' => Taxi::count(),
                'todayTrips' => Viaje::whereDate('created_at', $today)->count(),
                'todayRevenue' => (float) Viaje::whereDate('created_at', $today)->where('status', 'completed')->sum('price'),
                'averageRating' => round((float) Conductor::avg('rating'), 2),
                'weeklyRevenue' => (float) Viaje::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->where('status', 'completed')->sum('price'),
                'monthlyRevenue' => (float) Viaje::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->where('status', 'completed')->sum('price'),
            ]);
        }

        public function monthlyStats(Request $request) {
            $validated = $request->validate([
                'year' => 'nullable|integer|min:2000|max:2100',
                'month' => 'nullable|integer|min:1|max:12',
            ]);

            $year = (int) ($validated['year'] ?? now()->year);
            $month = (int) ($validated['month'] ?? now()->month);

            $from = Carbon::create($year, $month, 1, 0, 0, 0)->startOfMonth();
            $to = (clone $from)->endOfMonth();

            $completedTrips = Viaje::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', 'completed')
                ->count();

            $cancelledTrips = Viaje::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', 'cancelled')
                ->count();

            $revenue = (float) Viaje::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('status', 'completed')
                ->sum('price');

            $minDate = Viaje::min('created_at');
            $maxDate = Viaje::max('created_at');
            $minYear = $minDate ? Carbon::parse($minDate)->year : now()->year;
            $maxYear = $maxDate ? Carbon::parse($maxDate)->year : now()->year;

            return response()->json([
                'year' => $year,
                'month' => $month,
                'completedTrips' => $completedTrips,
                'cancelledTrips' => $cancelledTrips,
                'revenue' => $revenue,
                'minYear' => $minYear,
                'maxYear' => $maxYear,
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

        public function disableUser(User $user)
        {
            $user->is_disabled = true;
            $user->save();

            if (($user->role ?? null) === 'conductor') {
                $user->loadMissing('conductor.taxi');
                if ($user->conductor) {
                    $user->conductor->is_active = false;
                    $user->conductor->save();
                    if ($user->conductor->taxi && $user->conductor->taxi->status !== 'offline') {
                        $user->conductor->taxi->update(['status' => 'offline']);
                    }
                }
            }

            return response()->json($user->fresh(['conductor']));
        }

        public function conductorEarningsReport(Conductor $conductor)
        {
            $rows = Viaje::query()
                ->where('conductor_id', $conductor->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->select(['id', 'status', 'price', 'created_at'])
                ->orderBy('created_at')
                ->get();

            $grouped = [];
            $totals = [
                'completedTrips' => 0,
                'cancelledTrips' => 0,
                'revenue' => 0.0,
            ];

            foreach ($rows as $row) {
                $key = Carbon::parse($row->created_at)->format('Y-m');
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'month' => $key,
                        'completedTrips' => 0,
                        'cancelledTrips' => 0,
                        'revenue' => 0.0,
                    ];
                }

                if ($row->status === 'completed') {
                    $grouped[$key]['completedTrips']++;
                    $grouped[$key]['revenue'] += (float) ($row->price ?? 0);
                    $totals['completedTrips']++;
                    $totals['revenue'] += (float) ($row->price ?? 0);
                } elseif ($row->status === 'cancelled') {
                    $grouped[$key]['cancelledTrips']++;
                    $totals['cancelledTrips']++;
                }
            }

            $months = array_values($grouped);
            usort($months, fn ($a, $b) => strcmp($a['month'], $b['month']));

            $conductor->loadMissing('user:id,name,email,phone');

            return response()->json([
                'conductor' => [
                    'id' => $conductor->id,
                    'name' => $conductor->user?->name,
                    'email' => $conductor->user?->email,
                    'phone' => $conductor->user?->phone,
                ],
                'totals' => $totals,
                'months' => $months,
            ]);
        }

        public function clientTripsReport(User $user)
        {
            if (($user->role ?? null) !== 'pasajero') {
                return response()->json(['message' => 'El usuario no es un cliente.'], 400);
            }

            $trips = Viaje::query()
                ->where('pasajero_id', $user->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->select(['id', 'status', 'price', 'pickup_address', 'dropoff_address', 'created_at'])
                ->latest()
                ->get();

            return response()->json([
                'client' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'trips' => $trips,
            ]);
        }
    }