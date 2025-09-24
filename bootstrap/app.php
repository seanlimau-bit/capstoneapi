<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use App\Http\Middleware\Auth0Middleware;
use App\Http\Middleware\CorsImageMiddleware;
use App\Http\Middleware\AdminMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '/api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // REMOVE this line - it's causing the 500 error
        // $middleware->append(\App\Http\Middleware\CorsImageMiddleware::class);
        
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
        
        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'cors.image' => \App\Http\Middleware\CorsImageMiddleware::class,
            'qa' => \App\Http\Middleware\QAMiddleware::class,// Add as alias instead
        ]);
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