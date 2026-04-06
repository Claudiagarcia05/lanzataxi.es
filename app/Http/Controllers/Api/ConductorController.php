<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Conductor;
    use Illuminate\Http\Request;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Schema;

    class ConductorController extends Controller {

        private function userSelectColumns(): array
        {
            $cols = ['id', 'name', 'email'];

            foreach (['phone', 'avatar', 'is_disabled'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $cols[] = $col;
                }
            }

            return $cols;
        }

        public function profile(Request $solicitud) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor) {

                return response()->json(['message' => 'conductor profile not found'], 404);
            }

            $estadoTaxi = $conductor->is_active ? 'available' : 'offline';
            $taxi = $conductor->ensureTaxiExists($estadoTaxi);
            if ($conductor->is_active && $taxi->status === 'offline') {
                $taxi->update(['status' => 'available']);
            }

            $userCols = $this->userSelectColumns();

            return response()->json($conductor->load([
                'user' => fn ($q) => $q->select($userCols),
                'taxi',
            ]));
        }

        public function index() {

            $userCols = $this->userSelectColumns();

            return response()->json(
                conductor::with([
                    'user' => fn ($q) => $q->select($userCols),
                    'taxi:id,conductor_id,plate,model,capacity,color,status',
                ])
                    ->latest()
                    ->get()
            );
        }

        public function store(Request $solicitud) {
            $validado = $solicitud->validate([
                'user_id' => 'required|exists:users,id',
                'license_number' => 'required|string|unique:conductors,license_number',
                'rating' => 'nullable|numeric|min:0|max:5',
                'is_active' => 'nullable|boolean',
            ]);

            $conductor = conductor::create($validado);

            if (($validado['is_active'] ?? false) === true) {
                $conductor->ensureTaxiExists('available');
            }

            return response()->json($conductor, 201);
        }

        public function show(conductor $conductor) {
            $userCols = $this->userSelectColumns();

            return response()->json($conductor->load([
                'user' => fn ($q) => $q->select($userCols),
                'taxi',
            ]));
        }

        public function update(Request $solicitud, conductor $conductor) {
            $validado = $solicitud->validate([
                'license_number' => 'sometimes|string|unique:conductors,license_number,' . $conductor->id,
                'rating' => 'sometimes|numeric|min:0|max:5',
                'is_active' => 'sometimes|boolean',
            ]);

            $conductor->update($validado);

            return response()->json($conductor);
        }

        public function destroy(conductor $conductor) {
            $conductor->delete();

            return response()->json(['message' => 'conductor deleted']);
        }

        public function status(Request $solicitud) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor) {

                return response()->json(['message' => 'conductor profile not found'], 404);
            }

            if ($conductor->is_active) {
                $taxi = $conductor->ensureTaxiExists('available');
                if ($taxi->status === 'offline') {
                    $taxi->update(['status' => 'available']);
                }
            }

            $ahora = Carbon::now();

            $claveMes = $ahora->format('Y-m');
            if (($conductor->online_month ?? null) !== $claveMes) {
                $conductor->online_month = $claveMes;
                $conductor->online_seconds_month = 0;
                if ($conductor->is_active) {
                    $conductor->online_since = $ahora;
                }
                $conductor->save();
            }

            if ($conductor->is_active && !$conductor->online_since) {
                $conductor->online_since = $ahora;
                $conductor->save();
            }

            $segundosConectado = (int) ($conductor->online_seconds ?? 0);
            if ($conductor->is_active && $conductor->online_since) {
                $segundosConectado += $conductor->online_since->diffInSeconds($ahora);
            }

            $segundosConectadoMes = (int) ($conductor->online_seconds_month ?? 0);
            if ($conductor->is_active && $conductor->online_since) {
                $segundosConectadoMes += $conductor->online_since->diffInSeconds($ahora);
            }

            return response()->json([
                'is_active' => (bool) $conductor->is_active,
                'rating' => $conductor->rating,
                'taxi' => $conductor->taxi,
                'connected_seconds' => $segundosConectado,
                'connected_hours' => round($segundosConectado / 3600, 2),
                'connected_seconds_month' => $segundosConectadoMes,
                'connected_hours_month' => round($segundosConectadoMes / 3600, 2),
                'online_month' => $conductor->online_month,
                'online_since' => $conductor->online_since?->toIso8601String(),
            ]);
        }

        public function updateStatus(Request $solicitud) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor) {

                return response()->json(['message' => 'conductor profile not found'], 404);
            }

            $validado = $solicitud->validate([
                'is_active' => 'required|boolean',
            ]);

            $conductor->update(['is_active' => $validado['is_active']]);

            if ($validado['is_active'] === true) {
                $taxi = $conductor->ensureTaxiExists('available');
                if ($taxi->status === 'offline') {
                    $taxi->update(['status' => 'available']);
                }
            } else {
                $taxi = $conductor->taxi;
                if ($taxi && $taxi->status !== 'offline') {
                    $taxi->update(['status' => 'offline']);
                }
            }

            return response()->json($conductor);
        }

        public function nearbyconductors(Request $solicitud) {
            $validado = $solicitud->validate([
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
                'radius' => 'nullable|numeric|min:1|max:50',
            ]);

            $radio = $validado['radius'] ?? 5;

            try {
                $conductores = conductor::with(['user:id,name', 'taxi:id,conductor_id,plate,model,status'])
                    ->where('is_active', true)
                    ->whereHas('user')
                    ->whereHas('taxi', function ($query) {
                        $query->where('status', 'available');
                    })
                    ->limit(10)
                    ->get()
                    ->filter(function ($conductor) {

                        return $conductor->user && $conductor->taxi;
                    })
                    ->map(function ($conductor) {

                        return [
                            'id' => $conductor->id,
                            'conductor_name' => $conductor->user?->name ?? 'Sin nombre',
                            'taxi_model' => $conductor->taxi?->model ?? 'N/A',
                            'taxi_plate' => $conductor->taxi?->plate ?? 'N/A',
                            'rating' => $conductor->rating ?? 4.5,
                            'distance' => round(rand(5, 50) / 10, 1),
                        ];
                    })
                    ->values();

                return response()->json($conductores);
            } catch (\Exception $e) {
                \Log::error('Error en nearbyconductors: ' . $e->getMessage());
                
                return response()->json([]);
            }
        }
    }