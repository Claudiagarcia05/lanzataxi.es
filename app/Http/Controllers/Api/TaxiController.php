<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Taxi;
    use Illuminate\Http\Request;

    class TaxiController extends Controller {
        public function index() {
            
            return response()->json(Taxi::with('conductor.user:id,name')->latest()->get());
        }

        public function store(Request $solicitud) {
            $validado = $solicitud->validate([
                'conductor_id' => 'required|exists:conductors,id',
                'plate' => 'required|string|unique:taxis,plate',
                'model' => 'required|string',
                'capacity' => 'required|integer|min:1',
                'color' => 'nullable|string',
                'status' => 'nullable|in:available,busy,offline',
            ]);

            $taxi = Taxi::create($validado);

            return response()->json($taxi, 201);
        }

        public function show(Taxi $taxi) {

            return response()->json($taxi->load('conductor.user:id,name,email,phone'));
        }

        public function update(Request $solicitud, Taxi $taxi) {
            $validado = $solicitud->validate([
                'conductor_id' => 'sometimes|exists:conductors,id',
                'plate' => 'sometimes|string|unique:taxis,plate,' . $taxi->id,
                'model' => 'sometimes|string',
                'capacity' => 'sometimes|integer|min:1',
                'color' => 'sometimes|nullable|string',
                'status' => 'sometimes|in:available,busy,offline',
            ]);

            $taxi->update($validado);

            return response()->json($taxi);
        }

        public function destroy(Taxi $taxi) {
            $taxi->delete();

            return response()->json(['message' => 'Taxi deleted']);
        }

        public function available() {
            try {
                $taxis = Taxi::with('conductor.user:id,name')
                    ->where('status', 'available')
                    ->whereHas('conductor.user')
                    ->get()
                    ->map(function ($taxi) {

                        return [
                            'id' => $taxi->id,
                            'plate' => $taxi->plate,
                            'model' => $taxi->model,
                            'status' => $taxi->status,
                            'conductor_name' => $taxi->conductor?->user?->name ?? 'Sin conductor',
                            'conductor_id' => $taxi->conductor?->id,
                        ];
                    });

                return response()->json($taxis);
            } catch (\Exception $e) {
                \Log::error('Error en available taxis: ' . $e->getMessage());
                
                return response()->json([]);
            }
        }
    }