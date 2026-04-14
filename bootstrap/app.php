<?php

    use Illuminate\Foundation\Application;
    use Illuminate\Foundation\Configuration\Exceptions;
    use Illuminate\Foundation\Configuration\Middleware;

    return Application::configure(basePath: dirname(__DIR__))
        ->withRouting(
            web: __DIR__.'/../routes/web.php',
            api: __DIR__.'/../routes/api.php',
            commands: __DIR__.'/../routes/console.php',
            health: '/up',
        )
        ->withMiddleware(function (Middleware $middleware): void {
            // La app usa autenticación por Bearer token en /api (Sanctum tokens).
            // `statefulApi()` aplica middleware tipo "web" (sesión + CSRF) a /api, lo que provoca 419.
            // Por eso lo desactivamos y mantenemos /api como stateless.
            // $middleware->statefulApi();

            // Cambiar el idioma solo setea una cookie; no merece romper por CSRF en producción.
            // Además, al ser una preferencia no sensible, lo eximimos de validación CSRF.
            $middleware->validateCsrfTokens(except: [
                'locale',
            ]);

            $middleware->append([
                \App\Http\Middleware\EncabezadosSeguridad::class,
            ]);

            $middleware->web(append: [
                // Locale basado en cookie (o Accept-Language como fallback)
                // Debe ejecutarse DESPUÉS de EncryptCookies para leer el valor desencriptado.
                \App\Http\Middleware\EstablecerLocale::class,
                \App\Http\Middleware\HandleInertiaRequests::class,
                \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            ]);

            $middleware->alias([
                'role' => \App\Http\Middleware\MiddlewareRol::class,
                'account.enabled' => \App\Http\Middleware\VerificarCuentaHabilitada::class,
                'conductor.approved' => \App\Http\Middleware\VerificarConductorAprobado::class,
            ]);
        })
        ->withExceptions(function (Exceptions $exceptions): void {
            //
        })->create();