<?php

    /*
    |--------------------------------------------------------------------------
    | Testing / Diagnostic Routes
    |--------------------------------------------------------------------------
    | Rutas de prueba para facilitar validaciones manuales durante desarrollo.
    | IMPORTANTE: en producción conviene protegerlas (por entorno, IP, auth o
    | deshabilitarlas) porque pueden crear usuarios y emitir tokens.
    */

    use App\Http\Controllers\AuthSessionController;
    use App\Models\User;
    use Illuminate\Support\Facades\Route;

    Route::get('/test-login-flow', function () {
        // Crea/rehace un usuario de prueba y devuelve un token + URL para establecer sesión.
        User::where('email', 'test@test.com')->delete();
        
        $usuario = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role' => 'pasajero'
        ]);
        
        $token = $usuario->createToken('api')->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'session_login_url' => "/auth/session-login?token=" . urlencode($token),
            'user_id' => $usuario->id,
            'instructions' => [
                // Instrucciones pensadas para tests manuales en navegador.
                '1. Navigate to session_login_url in browser',
                '2. Check if you land on /dashboard/home or /login',
                '3. Check browser console for logs'
            ]
        ]);
    });

    Route::get('/test-check-session', function () {
        // Devuelve información útil para depurar si la sesión está establecida.
        return response()->json([
            'authenticated' => auth()->check(),
            'user' => auth()->user() ? [
                'id' => auth()->user()->id,
                'email' => auth()->user()->email,
                'role' => auth()->user()->role
            ] : null,
            'session_id' => session()->getId(),
            'session_data' => session()->all()
        ]);
    });