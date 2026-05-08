<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanAccessAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Centralized check via User model helper
        if (!$user || !$user->canAccessAdminPanel()) {
            return redirect()->route('home')->with('error', __('You do not have permission to access the admin area.'));
        }

        return $next($request);
    }
}
