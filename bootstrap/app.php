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
        apiPrefix: 'api',  // Changed from '/api' to 'api'
    )
    ->withMiddleware(function (Middleware $middleware) {
 //       $middleware->api(prepend: [
 //           \App\Http\Middleware\CorsMiddleware::class,
   //     ]);
        
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'cors.image' => \App\Http\Middleware\CorsImageMiddleware::class,
            'qa' => \App\Http\Middleware\QAMiddleware::class,
            'can.manage.users' => \App\Http\Middleware\CanManageUsers::class,
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