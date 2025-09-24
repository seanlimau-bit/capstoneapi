<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->guest()) {
            // Return JSON if it's an API call
            if ($request->expectsJson() || Str::startsWith($request->path(), 'api')) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            // Redirect to login route
            return redirect()->guest(route('login'));
        }
        return $next($request);
    }
}