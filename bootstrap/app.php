<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\CloudflareRealIp::class);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(
            prepend: [
                // Runs before StartSession so session.cookie / session.path
                // mutations take effect on the admin session bootstrap.
                \App\Http\Middleware\ScopeAdminSession::class,
            ],
            append: [
                HandleAppearance::class,
                HandleInertiaRequests::class,
                AddLinkHeadersForPreloadedAssets::class,
                \App\Http\Middleware\SessionSecurityMiddleware::class,
                \App\Http\Middleware\UpdateUserLastActive::class,
                \App\Http\Middleware\CDNCorrelationMiddleware::class,
                \App\Http\Middleware\SecurityHeadersMiddleware::class,
                \App\Http\Middleware\VerifyCDNRequestMiddleware::class,
            ],
        );
        $middleware->alias([
            'admin' => \App\Http\Middleware\CanAccessAdmin::class,
        ]);

        // Route guests requesting /admin/* to the admin login form, not the
        // frontend Fortify login. Without this, an unauthenticated visit to
        // /admin/dashboard bounces to /login (frontend) → user signs in
        // there → Fortify redirects to /dashboard (frontend home) → user
        // never reaches admin. After PR-D's session isolation, signing in
        // on the frontend cannot reach admin at all (different cookies),
        // making this UX bug from the pre-isolation era hard-blocking.
        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.auth.login');
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
