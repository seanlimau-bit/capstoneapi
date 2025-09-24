<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsImageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only add headers for image routes and ensure response has headers
        if (str_starts_with($request->path(), 'images/') && method_exists($response, 'headers')) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', '*');
        }
        
        return $response;
    }
}