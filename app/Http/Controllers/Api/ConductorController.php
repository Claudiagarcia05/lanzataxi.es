<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Conductor;
    use Illuminate\Http\Request;
    use Illuminate\Support\Carbon;

    class ConductorController extends Controller {
        public function profile(Request $solicitud) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor) {

                return response()->json(['message' => 'conductor profile not found'], 404);
            }

            $taxiStatus = $conductor->is_active ? 'available' : 'offline';
            $taxi = $conductor->ensureTaxiExists($taxiStatus);
            if ($conductor->is_active && $taxi->status === 'offline') {
                $taxi->update(['status' => 'available']);
            }

            return response()->json($conductor->load(['user:id,name,email,phone,avatar,is_disabled', 'taxi']));
        }

        public function index() {

            return response()->json(
                conductor::with(['user:id,name,email,phone,is_disabled', 'taxi:id,conductor_id,plate,model,capacity,color,status'])
                    ->latest()
                    ->get()
            );
        }

        public function store(Request $solicitud) {
            $validated = $solicitud->validate([
                'user_id' => 'required|exists:users,id',
                'license_number' => 'required|string|unique:conductors,license_number',
                'rating' => 'nullable|numeric|min:0|max:5',
                'is_active' => 'nullable|boolean',
            ]);

            $conductor = conductor::create($validated);

            if (($validated['is_active'] ?? false) === true) {
                $conductor->ensureTaxiExists('available');
            }

            return response()->json($conductor, 201);
        }

        public function show(conductor $conductor) {

            return response()->json($conductor->load(['user:id,name,email,phone,is_disabled', 'taxi']));
        }

        public function update(Request $solicitud, conductor $conductor) {
            $validated = $solicitud->validate([
                'license_number' => 'sometimes|string|unique:conductors,license_number,' . $conductor->id,
                'rating' => 'sometimes|numeric|min:0|max:5',
                'is_active' => 'sometimes|boolean',
            ]);

            $conductor->update($validated);

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

            $now = Carbon::now();

            $monthKey = $now->format('Y-m');
            if (($conductor->online_month ?? null) !== $monthKey) {
                $conductor->online_month = $monthKey;
                $conductor->online_seconds_month = 0;
                if ($conductor->is_active) {
                    $conductor->online_since = $now;
                }
                $conductor->save();
            }

            if ($conductor->is_active && !$conductor->online_since) {
                $conductor->online_since = $now;
                $conductor->save();
            }

            $connectedSeconds = (int) ($conductor->online_seconds ?? 0);
            if ($conductor->is_active && $conductor->online_since) {
                $connectedSeconds += $conductor->online_since->diffInSeconds($now);
            }

            $connectedSecondsMonth = (int) ($conductor->online_seconds_month ?? 0);
            if ($conductor->is_active && $conductor->online_since) {
                $connectedSecondsMonth += $conductor->online_since->diffInSeconds($now);
            }

            return response()->json([
                'is_active' => (bool) $conductor->is_active,
                'rating' => $conductor->rating,
                'taxi' => $conductor->taxi,
                'connected_seconds' => $connectedSeconds,
                'connected_hours' => round($connectedSeconds / 3600, 2),
                'connected_seconds_month' => $connectedSecondsMonth,
                'connected_hours_month' => round($connectedSecondsMonth / 3600, 2),
                'online_month' => $conductor->online_month,
                'online_since' => $conductor->online_since?->toIso8601String(),
            ]);
        }

        public function updateStatus(Request $solicitud) {
            $conductor = $solicitud->user()->conductor;

            if (!$conductor) {

                return response()->json(['message' => 'conductor profile not found'], 404);
            }

            $validated = $solicitud->validate([
                'is_active' => 'required|boolean',
            ]);

            $conductor->update(['is_active' => $validated['is_active']]);

            if ($validated['is_active'] === true) {
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
            $validated = $solicitud->validate([
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
                'radius' => 'nullable|numeric|min:1|max:50',
            ]);

            $radius = $validated['radius'] ?? 5;

            try {
                $conductors = conductor::with(['user:id,name', 'taxi:id,conductor_id,plate,model,status'])
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

                return response()->json($conductors);
            } catch (\Exception $e) {
                \Log::error('Error en nearbyconductors: ' . $e->getMessage());
                
                return response()->json([]);
            }
        }
    }