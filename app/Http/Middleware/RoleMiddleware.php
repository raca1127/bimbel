<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage: add alias 'role' => \App\Http\Middleware\RoleMiddleware::class in Kernel.php
     * and use middleware('role:guru') or middleware('role:pelajar') in routes.
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = Auth::user();
        if (! $user || ($user->role ?? '') !== $role) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
