<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
	// AdminMiddleware - for full admin area access
	public function handle(Request $request, Closure $next)
	{
		if (!auth('web')->check()) {
        // use guest() so Laravel remembers intended URL
			return redirect()->guest(url('/login'));
		}

		if (!auth('web')->user()->canAccessAdmin()) {
			abort(403, 'Access denied. Admin privileges required.');
		}

		return $next($request);
	}
}