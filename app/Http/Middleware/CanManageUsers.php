<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CanManageUsers
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Authentication required');
        }

        // Must be admin AND have user management permission
        if (!auth()->user()->canAccessAdmin() || !auth()->user()->hasPermission('list_users')) {
            abort(403, 'Unauthorized. User management permissions required.');
        }

        return $next($request);
    }
}