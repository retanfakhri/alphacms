<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\CDN\CDNRequestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Bootstraps a per-request correlation id for the CDN subsystem (and any
 * other code that wants to grep logs by trace_id).
 *
 * Honours an inbound X-Request-ID / X-Trace-ID if the upstream proxy
 * already issued one (e.g. Cloudflare → Cf-Ray).
 */
class CDNCorrelationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = $request->header('X-Request-ID') ?: null;
        $traceId = $request->header('X-Trace-ID')
            ?: $request->header('Cf-Ray') // Cloudflare
            ?: null;

        // Reset and (optionally) seed
        CDNRequestContext::reset();

        if ($requestId !== null || $traceId !== null) {
            CDNRequestContext::set($requestId, $traceId);
        }

        // Make ids available to all log channels via shared context.
        Log::shareContext(CDNRequestContext::logContext());

        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Request-ID', CDNRequestContext::requestId());
            $response->header('X-Trace-ID', CDNRequestContext::traceId());

            // Debug headers — يُعرض في non-production فقط لتجنّب information leakage.
            if (! app()->environment('production')) {
                $response->header('X-App-Env', app()->environment());
            }
        }

        return $response;
    }
}
