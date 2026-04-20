<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Taxi;
    use Illuminate\Http\Request;

    /**
     * CRUD y consultas de taxis.
     */
    class TaxiController extends Controller {
        /**
         * Normaliza matrícula a formato español típico: `1234 ABC`.
         *
         * - Elimina separadores y caracteres no alfanuméricos.
         * - Inserta espacio si encaja con el patrón 4 dígitos + 3 letras.
         */
        private function normalizePlate(?string $plate): ?string {
            if ($plate === null) return null;

            $raw = strtoupper(trim($plate));
            $compact = preg_replace('/[^A-Z0-9]/', '', $raw);
            if (!is_string($compact)) $compact = '';

            if (preg_match('/^(\d{4})([A-Z]{3})$/', $compact, $m)) {

                return $m[1] . ' ' . $m[2];
            }

            return $raw;
        }

        public function index() {
            // Lista para administración/gestión.
            return response()->json(Taxi::with('conductor.user:id,name')->latest()->get());
        }

        /**
         * Crea un taxi (normaliza matrícula antes de validar/guardar).
         */
        public function store(Request $solicitud) {
            if ($solicitud->has('plate')) {
                $solicitud->merge([
                    'plate' => $this->normalizePlate($solicitud->input('plate')),
                ]);
            }

            $validado = $solicitud->validate([
                'conductor_id' => 'required|exists:conductors,id',
                'plate' => ['required', 'string', 'unique:taxis,plate', 'regex:/^\d{4}[\s-]?[A-Za-z]{3}$/'],
                'model' => 'required|string',
                'capacity' => 'required|integer|min:1',
                'color' => 'nullable|string',
                'status' => 'nullable|in:available,busy,offline',
            ]);

            $taxi = Taxi::create($validado);

            return response()->json($taxi, 201);
        }

        /**
         * Muestra un taxi.
         */
        public function show(Taxi $taxi) {

            return response()->json($taxi->load('conductor.user:id,name,email,phone'));
        }

        /**
         * Actualiza un taxi (normaliza matrícula si viene incluida).
         */
        public function update(Request $solicitud, Taxi $taxi) {
            if ($solicitud->has('plate')) {
                $solicitud->merge([
                    'plate' => $this->normalizePlate($solicitud->input('plate')),
                ]);
            }

            $validado = $solicitud->validate([
                'conductor_id' => 'sometimes|exists:conductors,id',
                'plate' => ['sometimes', 'string', 'unique:taxis,plate,' . $taxi->id, 'regex:/^\d{4}[\s-]?[A-Za-z]{3}$/'],
                'model' => 'sometimes|string',
                'capacity' => 'sometimes|integer|min:1',
                'color' => 'sometimes|nullable|string',
                'status' => 'sometimes|in:available,busy,offline',
            ]);

            $taxi->update($validado);

            return response()->json($taxi);
        }

        /**
         * Elimina un taxi.
         */
        public function destroy(Taxi $taxi) {
            $taxi->delete();

            return response()->json(['message' => 'Taxi deleted']);
        }

        /**
         * Lista taxis disponibles.
         *
         * Nota: filtra también por existencia de conductor->user.
         */
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