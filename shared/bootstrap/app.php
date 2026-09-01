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
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $exception): void {
            error_log(sprintf(
                'APP_EXCEPTION [%s]: %s',
                $exception::class,
                $exception->getMessage(),
            ));
        });

        $exceptions->shouldRenderJsonWhen(
            fn ($request, \Throwable $exception): bool => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
