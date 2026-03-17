<?php

    // Rutas API (JSON) para la aplicación:
    // - Auth (registro/login y endpoints protegidos con Sanctum)
    // - Perfil de usuario, cartera, notificaciones y favoritos
    // - Viajes (crear, cancelar, tracking, flujos de conductor/admin)
    // Nota: la mayoría de rutas están bajo `auth:sanctum`.

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

    // --- Público (sin sesión) ---
    // Registro e inicio de sesión
    Route::post('/register', [AutenticacionController::class, 'register']);
    Route::post('/login', [AutenticacionController::class, 'login']);

    // Datos de disponibilidad (lectura pública)
    Route::get('/available-taxis', [TaxiController::class, 'available']);
    Route::get('/nearby-conductors', [ConductorController::class, 'nearbyconductors']);

    // --- Protegido (requiere token Sanctum) ---
    Route::middleware(['auth:sanctum', 'account.enabled'])->group(function () {
        // Sesión API
        Route::get('/me', [AutenticacionController::class, 'me']);
        Route::post('/logout', [AutenticacionController::class, 'logout']);

        // Perfil / usuario
        Route::post('/user/profile', [UsuarioController::class, 'updateProfile']);
        Route::put('/user/profile', [UsuarioController::class, 'updateProfile']);
        Route::post('/user/avatar', [UsuarioController::class, 'uploadAvatar']);
        Route::put('/user/password', [UsuarioController::class, 'updatePassword']);
        Route::put('/user/preferences', [UsuarioController::class, 'updatePreferences']);
        Route::delete('/user', [UsuarioController::class, 'deleteAccount']);

        // Cartera virtual
        Route::get('/wallet/balance', [CarteraController::class, 'getBalance']);
        Route::get('/wallet/debts', [CarteraController::class, 'getDebtSummary']);
        Route::get('/wallet/transactions', [CarteraController::class, 'getTransactions']);
        Route::post('/wallet/add', [CarteraController::class, 'addFunds']);
        Route::post('/wallet/use', [CarteraController::class, 'useFunds']);
        Route::post('/wallet/withdraw', [CarteraController::class, 'withdrawFunds']);

        // Rutas favoritas
        Route::get('/favorites', [RutaFavoritaController::class, 'index']);
        Route::post('/favorites', [RutaFavoritaController::class, 'store']);
        Route::put('/favorites/{favorite}', [RutaFavoritaController::class, 'update']);
        Route::delete('/favorites/{favorite}', [RutaFavoritaController::class, 'destroy']);
        Route::post('/favorites/reorder', [RutaFavoritaController::class, 'reorder']);

        // Notificaciones
        Route::get('/notifications', [NotificacionController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificacionController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificacionController::class, 'markAllAsRead']);
        Route::delete('/notifications/{notification}', [NotificacionController::class, 'destroy']);

        // Viajes del usuario (pasajero)
        Route::get('/user/viajes', [ViajeController::class, 'userviajes']);

        // Viajes: creación, lectura y acciones del pasajero
        Route::post('/viajes', [ViajeController::class, 'store']);
        Route::get('/viajes/{viaje}', [ViajeController::class, 'show']);
        Route::get('/viajes/{viaje}/track', [ViajeController::class, 'track']);
        Route::patch('/viajes/{viaje}/cancel', [ViajeController::class, 'cancel']);
        Route::patch('/viajes/{viaje}/rate', [ViajeController::class, 'rate']);

        // Datos del conductor autenticado
        Route::get('/conductor/profile', [ConductorController::class, 'profile']);
        Route::get('/conductor/status', [ConductorController::class, 'status']);

        // --- Conductor (solo rol conductor) ---
        Route::middleware(['role:conductor', 'conductor.approved'])->group(function () {
            // Viajes asignados al conductor / disponibles
            Route::get('/conductor/viajes', [ViajeController::class, 'driverTrips']);
            Route::get('/conductor/viajes/available', [ViajeController::class, 'available']);

            // Acciones del conductor sobre un viaje
            Route::patch('/viajes/{viaje}/accept', [ViajeController::class, 'accept']);
            Route::patch('/viajes/{viaje}/start', [ViajeController::class, 'start']);
            Route::patch('/viajes/{viaje}/complete', [ViajeController::class, 'complete']);

            // Ubicación y estado del conductor
            Route::post('/conductor/ubicacion', [UbicacionController::class, 'update']);
            Route::patch('/conductor/status', [ConductorEstadoController::class, 'update']);
        });

        // --- Admin (solo rol admin) ---
        Route::middleware('role:admin')->group(function () {
            // Gestión/consulta de usuarios y viajes
            Route::get('/admin/users', [AdministradorController::class, 'users']);
            Route::get('/admin/viajes', [AdministradorController::class, 'viajes']);
            Route::get('/admin/pending-conductors', [AdministradorController::class, 'pendingconductors']);
            Route::get('/admin/stats', [AdministradorController::class, 'stats']);
            Route::get('/admin/monthly-stats', [AdministradorController::class, 'monthlyStats']);

            // Alta de administradores (solo desde panel admin)
            Route::post('/admin/admins', [AdministradorController::class, 'createAdmin']);

            // Aprobación / rechazo de taxistas
            Route::post('/admin/conductors/{conductor}/approve', [AdministradorController::class, 'approveConductor']);
            Route::post('/admin/conductors/{conductor}/reject', [AdministradorController::class, 'rejectConductor']);

            // Bajas (desactivar cuentas)
            Route::patch('/admin/users/{user}/disable', [AdministradorController::class, 'disableUser']);

            // Informes
            Route::get('/admin/conductors/{conductor}/earnings-report', [AdministradorController::class, 'conductorEarningsReport']);
            Route::get('/admin/clients/{user}/trips-report', [AdministradorController::class, 'clientTripsReport']);

            // CRUDs expuestos como API Resources
            Route::apiResource('conductors', ConductorController::class);
            Route::apiResource('taxis', TaxiController::class);

            // Reportes
            Route::get('/reports/viajes', [ViajeController::class, 'reports']);
        });

        // Pagos asociados a un viaje
        Route::post('/viajes/{viaje}/pago', [PagoController::class, 'store']);
        Route::get('/viajes/{viaje}/pago', [PagoController::class, 'show']);
        Route::post('/viajes/{viaje}/pago/stripe', [PagoController::class, 'processstripe']);
        Route::post('/viajes/{viaje}/pago/paypal', [PagoController::class, 'processPayPal']);
    });