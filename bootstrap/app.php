<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use App\Http\Middleware\Auth0Middleware;
use App\Http\Middleware\CorsImageMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '/api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware
        $middleware->append(CorsImageMiddleware::class);

        // Middleware specific to API routes
        $middleware->group('api', [
            Auth0Middleware::class,
            // Add more API middleware here if needed
        ]);

        // Other route groups (e.g., for web) can also be configured here
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, $request) {
            // Optional custom rendering
        });

        $exceptions->reportable(function (Throwable $e) {
            // Optional custom reporting
        });
    })
    ->create();
