<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCDNRequestMiddleware
{
    /**
     * Finding #1: Origin Exposure Hardening.
     * Prevents direct origin access by verifying a signed secret header 
     * sent only by the authorized CDN edge.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for local/testing environments
        if (! app()->environment('production')) {
            return $next($request);
        }

        $expectedSecret = config('cdn.edge_secret');

        if (! $expectedSecret) {
            return $next($request);
        }

        /**
         * We check for X-AlphaCMS-Edge-Secret. 
         * Cloudflare should be configured to inject this header 
         * in "Authenticated Origin Pulls" or via a Transform Rule.
         */
        $providedSecret = $request->header('X-AlphaCMS-Edge-Secret');

        if ($providedSecret !== $expectedSecret) {
            abort(403, 'Direct origin access is prohibited.');
        }

        return $next($request);
    }
}
