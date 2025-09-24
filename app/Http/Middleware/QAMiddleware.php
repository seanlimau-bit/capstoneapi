<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QAMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
	// QAMiddleware - for QA routes only
	public function handle(Request $request, Closure $next)
	{
	    if (!auth()->check()) {
			return redirect()->route('login')->with('error', 'Please log in to access QA area');
	    }
	    
	    if (!auth()->user()->canAccessQA()) {
	        abort(403, 'Access denied. QA privileges required.');
	    }
	    
	    return $next($request);
	}
}
