<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\RutaFavorita;
    use Illuminate\Http\Request;

    class RutaFavoritaController extends Controller {
        public function index(Request $solicitud) {
            try {
                if (!$solicitud->user()) {

                    return response()->json(['error' => 'Usuario no autenticado'], 401);
                }

                $usuario = $solicitud->user();
                
                $favoritos = $usuario->rutaFavoritas()
                    ->orderBy('order')
                    ->get();

                return response()->json($favoritos);
            } catch (\Exception $e) {
                \Log::error('Error al cargar favoritos: ' . $e->getMessage(), [
                    'exception' => $e,
                    'user_id' => $solicitud->user()?->id,
                ]);

                return response()->json(['error' => 'Error al cargar favoritos'], 500);
            }
        }

        public function store(Request $solicitud) {
            $validado = $solicitud->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
            ]);

            $ordenMaximo = $solicitud->user()->rutaFavoritas()->max('order') ?? -1;

            $favorite = $solicitud->user()->rutaFavoritas()->create([
                ...$validado,
                'order' => $ordenMaximo + 1,
            ]);

            return response()->json($favorite, 201);
        }

        public function update(Request $solicitud, RutaFavorita $favorite) {
            if ($favorite->user_id !== $solicitud->user()->id) {

                return response()->json(['message' => 'No autorizado'], 403);
            }

            $validado = $solicitud->validate([
                'name' => 'sometimes|string|max:255',
                'address' => 'sometimes|string|max:255',
                'lat' => 'sometimes|numeric',
                'lng' => 'sometimes|numeric',
                'order' => 'sometimes|integer|min:0',
            ]);

            $favorite->update($validado);

            return response()->json($favorite);
        }

        public function destroy(RutaFavorita $favorite) {
            if ($favorite->user_id !== auth()->id()) {

                return response()->json(['message' => 'No autorizado'], 403);
            }

            $favorite->delete();

            return response()->json(['message' => 'Ruta favorita eliminada']);
        }

        public function reorder(Request $solicitud) {
            $validado = $solicitud->validate([
                'favorites' => 'required|array',
                'favorites.*.id' => 'required|exists:rutas_favoritas,id',
                'favorites.*.order' => 'required|integer|min:0',
            ]);

            foreach ($validado['favorites'] as $item) {
                RutaFavorita::where('id', $item['id'])
                    ->where('user_id', $solicitud->user()->id)
                    ->update(['order' => $item['order']]);
            }

            return response()->json(['message' => 'Orden actualizado']);
        }
    }