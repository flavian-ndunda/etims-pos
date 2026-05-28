<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleMiddleware
 *
 * Protects routes by minimum required role.
 *
 * Usage in routes:
 *   ->middleware('role:admin')     // admin only
 *   ->middleware('role:manager')   // admin + manager
 *   ->middleware('role:cashier')   // everyone (same as just auth)
 *
 * Role hierarchy: admin > manager > cashier
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->hasRole($role)) {
            // Show a friendly access denied page instead of a 403
            return redirect()->route('dashboard')
                ->with('error', "Access denied. You need {$role} permissions to view that page.");
        }

        return $next($request);
    }
}
