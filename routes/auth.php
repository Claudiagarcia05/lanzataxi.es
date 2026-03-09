<?php

    // Rutas de autenticación (scaffolding Laravel)
    // - guest: login/registro/reset password
    // - auth: verificación de email, confirmación de contraseña, logout

    use App\Http\Controllers\Auth\AuthenticatedSessionController;
    use App\Http\Controllers\Auth\ConfirmablePasswordController;
    use App\Http\Controllers\Auth\EmailVerificationNotificationController;
    use App\Http\Controllers\Auth\EmailVerificationPromptController;
    use App\Http\Controllers\Auth\NewPasswordController;
    use App\Http\Controllers\Auth\PasswordController;
    use App\Http\Controllers\Auth\PasswordResetLinkController;
    use App\Http\Controllers\Auth\RegisteredUserController;
    use App\Http\Controllers\Auth\VerifyEmailController;
    use Illuminate\Support\Facades\Route;

    // Rutas para usuarios NO autenticados
    Route::middleware('guest')->group(function () {
        // Login (formulario)
        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->name('login');

        // Login (envío credenciales)
        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        // Registro (formulario)
        Route::get('register', [RegisteredUserController::class, 'create'])
            ->name('register');

        // Registro (envío datos)
        Route::post('register', [RegisteredUserController::class, 'store']);

        // Recuperación de contraseña (formulario)
        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
            ->name('password.request');

        // Recuperación de contraseña (envío email)
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('password.email');

        // Reset de contraseña (formulario con token)
        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
            ->name('password.reset');

        // Reset de contraseña (envío nueva contraseña)
        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->name('password.store');
    });

    // Rutas para usuarios autenticados
    Route::middleware('auth')->group(function () {
        // Aviso de verificación de email
        Route::get('verify-email', EmailVerificationPromptController::class)
            ->name('verification.notice');

        // Verificación de email (firmada y con rate limit)
        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        // Reenviar email de verificación
        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        // Confirmación de contraseña (formulario)
        Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
            ->name('password.confirm');

        // Confirmación de contraseña (envío)
        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

        // Actualización de contraseña (usuario autenticado)
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        // Logout
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });