<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Conductor;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;

class ConductorEstadoController extends Controller
{
    public function update(Request $solicitud)
    {
        $usuario = $solicitud->user();
        $conductor = Conductor::where('user_id', $usuario->id)->firstOrFail();
        $validado = $solicitud->validate([
            'is_active' => 'required|boolean',
        ]);

        $ahora = Carbon::now();
        $estabaActivo = (bool) $conductor->is_active;
        $estaraActivo = (bool) $validado['is_active'];

        $claveMes = $ahora->format('Y-m');
        if (($conductor->online_month ?? null) !== $claveMes) {
            $conductor->online_month = $claveMes;
            $conductor->online_seconds_month = 0;
            $conductor->online_since = $estaraActivo ? $ahora : null;
        }

        if ($estabaActivo && !$estaraActivo) {
            if ($conductor->online_since) {
                $transcurrido = $conductor->online_since->diffInSeconds($ahora);
                $conductor->online_seconds = (int) ($conductor->online_seconds ?? 0) + (int) $transcurrido;
                $conductor->online_seconds_month = (int) ($conductor->online_seconds_month ?? 0) + (int) $transcurrido;
                $conductor->online_since = null;
            }
        } elseif (!$estabaActivo && $estaraActivo) {
            if (!$conductor->online_since) {
                $conductor->online_since = $ahora;
            }
        } elseif ($estaraActivo && !$conductor->online_since) {
            $conductor->online_since = $ahora;
        }

        $conductor->is_active = $estaraActivo;
        $conductor->save();

        if ($conductor->is_active) {
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

        return response()->json([
            'success' => true,
            'is_active' => $conductor->is_active,
            'taxi' => $conductor->taxi,
            'connected_seconds' => (int) ($conductor->online_seconds ?? 0) + ($conductor->is_active && $conductor->online_since ? $conductor->online_since->diffInSeconds($ahora) : 0),
            'connected_hours' => round((((int) ($conductor->online_seconds ?? 0) + ($conductor->is_active && $conductor->online_since ? $conductor->online_since->diffInSeconds($ahora) : 0)) / 3600), 2),
            'connected_seconds_month' => (int) ($conductor->online_seconds_month ?? 0) + ($conductor->is_active && $conductor->online_since ? $conductor->online_since->diffInSeconds($ahora) : 0),
            'connected_hours_month' => round((((int) ($conductor->online_seconds_month ?? 0) + ($conductor->is_active && $conductor->online_since ? $conductor->online_since->diffInSeconds($ahora) : 0)) / 3600), 2),
            'online_month' => $conductor->online_month,
            'online_since' => $conductor->online_since?->toIso8601String(),
        ]);
    }
}
