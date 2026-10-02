<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('role:owner,manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! in_array($request->user()->role, $roles)) {
            abort(403, 'Access denied. You do not have permission to view this page.');
        }

        if ($request->user()->status !== 'active') {
            abort(403, 'Your account has been deactivated. Please contact an administrator.');
        }

        return $next($request);
    }
}
