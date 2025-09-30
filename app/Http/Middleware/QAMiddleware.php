<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QAMiddleware
{
    /**
     * Usage:
     *   ->middleware('qa')                                // requires qa_view
     *   ->middleware('qa:qa_approve_p2p3')               // requires qa_view + that perm
     *   ->middleware('qa:qa_approve_any,release_create') // requires qa_view + ANY of those
     */
    public function handle(Request $request, Closure $next, ...$extraPerms): Response
    {
        $user = $request->user();

        // Auth
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login')->with('error', 'Please log in to access QA area');
        }

        // Baseline QA access
        if (!$this->hasPerm($user, 'qa_view')) {
            return $this->deny($request, 'Access denied. QA privileges required.');
        }

        // Optional: require ANY of the extra permissions
        if (!empty($extraPerms)) {
            $ok = false;
            foreach ($extraPerms as $perm) {
                if ($this->hasPerm($user, $perm)) { $ok = true; break; }
            }
            if (!$ok) {
                return $this->deny($request, 'Access denied. Missing required QA permission.');
            }
        }

        return $next($request);
    }

    private function hasPerm($user, string $perm): bool
    {
        if (method_exists($user, 'hasPermission')) return $user->hasPermission($perm);
        if (method_exists($user, 'can'))          return $user->can($perm);
        return false;
    }

    private function deny(Request $request, string $message)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 403)
            : abort(403, $message);
    }
}
