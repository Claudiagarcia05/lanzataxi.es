<?php

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    | Todas las rutas bajo este archivo se consumen desde el frontend (Axios).
    | Se separan por responsabilidades (auth, usuario, viajes, conductor, admin,
    | pagos) y se protegen con middleware cuando es necesario.
    |
    | Nota: aunque haya validaciones en frontend, la seguridad real se aplica aquí
    | (auth, roles, cuenta habilitada, conductor aprobado, etc.).
    */

    use App\Http\Controllers\Api\ConductorController;
    use App\Http\Controllers\Api\UbicacionController;
    use App\Http\Controllers\Api\AutenticacionController;
    use App\Http\Controllers\Api\AdministradorController;
    use App\Http\Controllers\Api\PagoController;
    use App\Http\Controllers\Api\TaxiController;
    use App\Http\Controllers\Api\ViajeController;
    use App\Http\Controllers\Api\UsuarioController;
    use App\Http\Controllers\Api\RutaFavoritaController;
    use App\Http\Controllers\Api\NotificacionController;
    use App\Http\Controllers\Api\CarteraController;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Http\Request;
    use App\Http\Controllers\Api\ConductorEstadoController;
    use App\Http\Controllers\Api\GeocodingController;

    // Rutas públicas de autenticación: devuelven token/estado para iniciar sesión.
    Route::post('/register', [AutenticacionController::class, 'register']);
    Route::post('/login', [AutenticacionController::class, 'login']);

    // Datos públicos "de consulta" (sin sesión): usados para map/listados.
    Route::get('/available-taxis', [TaxiController::class, 'available']);
    Route::get('/nearby-conductors', [ConductorController::class, 'nearbyconductors']);

    // Proxy de geocodificación (Nominatim): evita CORS en navegador y permite cache/throttle.
    // Se deja público para que componentes de terceros (p.ej. Leaflet geocoder) puedan consumirlo.
    Route::get('/geocoding/search', [GeocodingController::class, 'search'])->middleware('throttle:60,1');
    Route::get('/geocoding/reverse', [GeocodingController::class, 'reverse'])->middleware('throttle:60,1');

    // Rutas privadas: requieren token válido (Sanctum) y que la cuenta esté habilitada.
    Route::middleware(['auth:sanctum', 'account.enabled'])->group(function () {
        Route::get('/me', [AutenticacionController::class, 'me']);
        Route::post('/logout', [AutenticacionController::class, 'logout']);

        // Perfil/ajustes de usuario (nombre, email, preferencias, password y borrado).
        // Se permiten POST/PUT para compatibilidad con diferentes clientes.
        Route::post('/user/profile', [UsuarioController::class, 'updateProfile']);
        Route::put('/user/profile', [UsuarioController::class, 'updateProfile']);
        Route::post('/user/avatar', [UsuarioController::class, 'uploadAvatar']);
        Route::put('/user/password', [UsuarioController::class, 'updatePassword']);
        Route::put('/user/preferences', [UsuarioController::class, 'updatePreferences']);
        Route::delete('/user', [UsuarioController::class, 'deleteAccount']);

        // Cartera: saldo, deudas y movimientos; además operaciones de añadir/usar/retirar.
        Route::get('/wallet/balance', [CarteraController::class, 'getBalance']);
        Route::get('/wallet/debts', [CarteraController::class, 'getDebtSummary']);
        Route::get('/wallet/transactions', [CarteraController::class, 'getTransactions']);
        Route::post('/wallet/add', [CarteraController::class, 'addFunds']);
        Route::post('/wallet/use', [CarteraController::class, 'useFunds']);
        Route::post('/wallet/withdraw', [CarteraController::class, 'withdrawFunds']);

        // Rutas favoritas del usuario (CRUD + reordenación).
        Route::get('/favorites', [RutaFavoritaController::class, 'index']);
        Route::post('/favorites', [RutaFavoritaController::class, 'store']);
        Route::put('/favorites/{favorite}', [RutaFavoritaController::class, 'update']);
        Route::delete('/favorites/{favorite}', [RutaFavoritaController::class, 'destroy']);
        Route::post('/favorites/reorder', [RutaFavoritaController::class, 'reorder']);

        // Notificaciones: listado, marcar como leídas y borrado.
        Route::get('/notifications', [NotificacionController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificacionController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificacionController::class, 'markAllAsRead']);
        Route::delete('/notifications/{notification}', [NotificacionController::class, 'destroy']);

        // Viajes del pasajero autenticado.
        Route::get('/user/viajes', [ViajeController::class, 'userviajes']);

        // Flujo de viajes: crear, ver, track (seguimiento), cancelar y valorar.
        Route::post('/viajes', [ViajeController::class, 'store']);
        Route::get('/viajes/{viaje}', [ViajeController::class, 'show']);
        Route::get('/viajes/{viaje}/track', [ViajeController::class, 'track']);
        Route::patch('/viajes/{viaje}/cancel', [ViajeController::class, 'cancel']);
        Route::patch('/viajes/{viaje}/rate', [ViajeController::class, 'rate']);

        // Datos del conductor asociado al usuario autenticado.
        Route::get('/conductor/profile', [ConductorController::class, 'profile']);
        Route::get('/conductor/status', [ConductorController::class, 'status']);

        // Acciones exclusivas de conductor (y además conductor aprobado).
        Route::middleware(['role:conductor', 'conductor.approved'])->group(function () {
            Route::get('/conductor/viajes', [ViajeController::class, 'driverTrips']);
            Route::get('/conductor/viajes/available', [ViajeController::class, 'available']);

            Route::patch('/viajes/{viaje}/accept', [ViajeController::class, 'accept']);
            Route::patch('/viajes/{viaje}/start', [ViajeController::class, 'start']);
            Route::patch('/viajes/{viaje}/complete', [ViajeController::class, 'complete']);

            Route::post('/conductor/ubicacion', [UbicacionController::class, 'update']);
            Route::patch('/conductor/status', [ConductorEstadoController::class, 'update']);
        });

        // Administración: endpoints de gestión, estadísticas y reportes.
        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/users', [AdministradorController::class, 'users']);
            Route::get('/admin/viajes', [AdministradorController::class, 'viajes']);
            Route::get('/admin/pending-conductors', [AdministradorController::class, 'pendingconductors']);
            Route::get('/admin/stats', [AdministradorController::class, 'stats']);
            Route::get('/admin/monthly-stats', [AdministradorController::class, 'monthlyStats']);

            Route::post('/admin/admins', [AdministradorController::class, 'createAdmin']);

            Route::post('/admin/conductors/{conductor}/approve', [AdministradorController::class, 'approveConductor']);
            Route::post('/admin/conductors/{conductor}/reject', [AdministradorController::class, 'rejectConductor']);

            Route::patch('/admin/users/{user}/disable', [AdministradorController::class, 'disableUser']);

            Route::get('/admin/conductors/{conductor}/earnings-report', [AdministradorController::class, 'conductorEarningsReport']);
            Route::get('/admin/clients/{user}/trips-report', [AdministradorController::class, 'clientTripsReport']);

            Route::apiResource('conductors', ConductorController::class);
            Route::apiResource('taxis', TaxiController::class);

            Route::get('/reports/viajes', [ViajeController::class, 'reports']);
        });

        // Pagos de viaje: creación/consulta y procesado con proveedores.
        Route::post('/viajes/{viaje}/pago', [PagoController::class, 'store']);
        Route::get('/viajes/{viaje}/pago', [PagoController::class, 'show']);
        Route::post('/viajes/{viaje}/pago/stripe', [PagoController::class, 'processstripe']);
        Route::post('/viajes/{viaje}/pago/paypal', [PagoController::class, 'processPayPal']);
    });