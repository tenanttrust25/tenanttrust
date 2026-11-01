<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ["tenant", "previous_tenant"], true)) {
            abort(403, "Access denied. Tenant access only.");
        }

        return $next($request);
    }
}
