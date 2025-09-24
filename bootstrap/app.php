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
        $middleware->trustProxies('*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\LocalizationMiddleware::class,
            \App\Http\Middleware\PerformanceMonitoringMiddleware::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'booking.owner' => \App\Http\Middleware\BookingOwnershipMiddleware::class,
            'vehicle.owner' => \App\Http\Middleware\VehicleOwnershipMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $throwable, $request) {
            // Ensure proper error handling for different environments
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Something went wrong.',
                    'error' => app()->environment('local') ? $throwable->getMessage() : 'Internal Server Error',
                ], 500);
            }

            // Let Laravel handle the default rendering with our custom error views
            return null;
        });
    })->create();
