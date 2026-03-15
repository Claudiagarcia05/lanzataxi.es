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
            $middleware->statefulApi();

            $middleware->append([
                \App\Http\Middleware\EncabezadosSeguridad::class,
            ]);

            $middleware->web(append: [
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