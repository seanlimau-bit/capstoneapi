<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\Auth0Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix:'/api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registering middleware to specific route groups
        $middleware->group('api', [ // Apply the middleware to API routes
            Auth0Middleware::class,
            // You can add other middlewares here that you want to apply to API routes
        ]);

        // Optional: if you need to register more middleware globally or for other routes
        $middleware->group('/', [
            // Other global middleware...
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
