<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Finding #3: Implement a unified security headers layer to protect 
     * against Clickjacking, MIME-type sniffing, and cross-origin leakage.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent Clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Control referrer information sent with requests
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Enforce HTTPS (HSTS) - only in production and if the request is secure
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        /**
         * Finding #2: Cache Poisoning & Proxy Protection.
         * Ensure 'Vary' header includes Accept-Encoding to prevent 
         * serving incorrectly compressed content from cache.
         */
        $vary = $response->headers->get('Vary');
        if ($vary) {
            if (! str_contains($vary, 'Accept-Encoding')) {
                $response->headers->set('Vary', $vary . ', Accept-Encoding');
            }
        } else {
            $response->headers->set('Vary', 'Accept-Encoding');
        }

        return $response;
    }
}
