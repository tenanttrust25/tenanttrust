<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            abort(403, 'Access denied. Admin access only.');
        }

        // Owners have their own portal. Send them there instead of showing a
        // bare 403 when they click an old /admin link (e.g. application emails).
        // Map the path across so /admin/rental-applications/23 lands on the
        // matching /owner page rather than dumping them at the dashboard.
        if (auth()->user()->role === 'owner') {
            $path = $request->getPathInfo();
            $target = str_starts_with($path, '/admin')
                ? '/owner' . substr($path, strlen('/admin'))
                : '/owner';

            return redirect($target ?: '/owner');
        }

        if (auth()->user()->role !== 'admin') {
            abort(403, 'Access denied. Admin access only.');
        }

        return $next($request);
    }
}
