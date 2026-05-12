<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scope admin requests to an isolated session cookie + path.
 *
 * Runs BEFORE Laravel's built-in StartSession middleware (registered via
 * `web(prepend: ...)` in bootstrap/app.php). On admin requests, mutates
 * the per-request `session.cookie` and `session.path` config. StartSession
 * then bootstraps using these mutated values, so:
 *
 *   - Frontend (path=/)         → cookie e.g. "myapp-session"
 *   - Admin    (path=/admin)    → cookie e.g. "myapp-admin-session"
 *
 * Threat model:
 *   - Session cookies are HttpOnly, so JavaScript cannot read either
 *     cookie via document.cookie.
 *   - Isolation buys: the BROWSER refuses to send the admin cookie on
 *     requests to non-/admin URLs. So an XSS payload running in the
 *     frontend context that triggers requests to public endpoints can
 *     only ride the frontend session — never the admin one. Conversely
 *     CSRF tokens are session-bound, so the frontend `_token` does not
 *     validate an admin POST.
 *
 * This is path-scoped cookie isolation, not JS-visibility hardening.
 */
class ScopeAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isAdminRequest($request)) {
            return $next($request);
        }

        $baseCookie = (string) config('session.cookie');

        config([
            'session.cookie'    => $this->adminCookieName($baseCookie),
            'session.path'      => '/admin',
            // Defensive: re-assert same_site so any upstream mutation doesn't
            // leak a more permissive value onto the admin cookie.
            'session.same_site' => config('session.same_site'),
        ]);

        // If anything in the request lifecycle eagerly resolved the session
        // driver before this middleware ran (Laravel's session.store binding
        // does this), the driver instance already has the frontend cookie
        // name baked in (Illuminate\Session\Store::$name is set at
        // construction). Rename in place rather than calling
        // app('session')->forgetDrivers() — forgetting destroys the in-memory
        // storage of the array session handler used in tests and breaks
        // session continuity across requests inside a single test.
        $adminCookie = (string) config('session.cookie');
        foreach (app('session')->getDrivers() as $driver) {
            if (method_exists($driver, 'setName')) {
                $driver->setName($adminCookie);
            }
        }

        return $next($request);
    }

    /**
     * Build the admin cookie name by inserting "-admin" before the trailing
     * "-session" segment of the base cookie name.
     *
     *   "myapp-session"       → "myapp-admin-session"
     *   "myapp-staging-session" → "myapp-staging-admin-session"
     *
     * Fallback: when the base name doesn't end in "-session" (custom override
     * via SESSION_COOKIE env), append "-admin" to whatever the base is. Keeps
     * the two names obviously paired even in non-default setups.
     */
    private function adminCookieName(string $base): string
    {
        if (Str::endsWith($base, '-session')) {
            return Str::replaceLast('-session', '-admin-session', $base);
        }

        return $base . '-admin';
    }

    private function isAdminRequest(Request $request): bool
    {
        return $request->is('admin') || $request->is('admin/*');
    }
}
