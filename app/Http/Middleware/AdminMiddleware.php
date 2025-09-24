<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
	// AdminMiddleware - for full admin area access
	public function handle(Request $request, Closure $next)
	{
	    if (!auth()->check()) {
	        return redirect()->route('login')->with('error', 'Please log in to access admin area');
	    }
	    
	    if (!auth()->user()->canAccessAdmin()) {
	        abort(403, 'Access denied. Admin privileges required.');
	    }
	    
	    return $next($request);
	}
}