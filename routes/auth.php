<?php

    /*
    |--------------------------------------------------------------------------
    | Auth Routes (web)
    |--------------------------------------------------------------------------
    | Rutas de autenticación para el flujo web tradicional (formularios, email
    | verification y reset de contraseña). Se agrupan por invitado (guest) y
    | autenticado (auth) para evitar accesos indebidos.
    */

    use App\Http\Controllers\Auth\AuthenticatedSessionController;
    use App\Http\Controllers\Auth\ConfirmablePasswordController;
    use App\Http\Controllers\Auth\EmailVerificationNotificationController;
    use App\Http\Controllers\Auth\EmailVerificationPromptController;
    use App\Http\Controllers\Auth\PasswordController;
    use App\Http\Controllers\Auth\PasswordResetLinkController;
    use App\Http\Controllers\Auth\RegisteredUserController;
    use App\Http\Controllers\Auth\VerifyEmailController;
    use Illuminate\Support\Facades\Route;

    // Invitados: login/registro y recuperación de contraseña.
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->name('login');

        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('register', [RegisteredUserController::class, 'create'])
            ->name('register');

        Route::post('register', [RegisteredUserController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
            ->name('password.request');

        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('password.email');

        Route::post('forgot-password/verify', [PasswordResetLinkController::class, 'verify'])
            ->name('password.verify');

        Route::post('forgot-password/reset', [PasswordResetLinkController::class, 'reset'])
            ->name('password.reset.store');
    });

    // Autenticados: verificación de email, confirmación de password y logout.
    Route::middleware('auth')->group(function () {
        Route::get('verify-email', EmailVerificationPromptController::class)
            ->name('verification.notice');

        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
            ->name('password.confirm');

        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });