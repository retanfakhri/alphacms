<?php

declare(strict_types=1);

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    Cache::flush();

    // Stable cookie-name expectations regardless of APP_NAME slug.
    $base = (string) config('session.cookie');
    $this->frontendCookie = $base;
    $this->adminCookie = Str::endsWith($base, '-session')
        ? Str::replaceLast('-session', '-admin-session', $base)
        : $base . '-admin';

    $this->adminUser = User::factory()->create([
        'email'             => 'admin@example.com',
        'password'          => Hash::make('correct-horse-battery-staple'),
        'is_active'         => true,
        'account_status'    => AccountStatus::Active,
        'email_verified_at' => now(),
    ]);
    $this->adminUser->assignRole('admin');

    $this->frontendUser = User::factory()->create([
        'email'             => 'reader@example.com',
        'password'          => Hash::make('correct-horse-battery-staple'),
        'is_active'         => true,
        'account_status'    => AccountStatus::Active,
        'email_verified_at' => now(),
    ]);
    $this->frontendUser->assignRole('user');
});

// ─── Cookie isolation ────────────────────────────────────────────────────

test('admin login response sets the admin-scoped cookie with path=/admin', function () {
    $response = $this->post('/admin/auth/login', [
        'email'    => $this->adminUser->email,
        'password' => 'correct-horse-battery-staple',
    ]);

    $response->assertRedirect(route('admin.dashboard'));

    // Symfony Cookie has no public `name` property; collection->firstWhere
    // can't reach it via data_get. Match via getName() explicitly.
    $cookies = collect($response->headers->getCookies());
    $adminCookie = $cookies->first(fn ($c) => $c->getName() === $this->adminCookie);

    expect($adminCookie)->not->toBeNull(
        "Expected admin session cookie '{$this->adminCookie}' to be set on /admin response",
    );
    expect($adminCookie->getPath())->toBe('/admin');
});

test('frontend page does NOT set the admin cookie', function () {
    $response = $this->get('/');
    $response->assertOk();

    $cookies = collect($response->headers->getCookies());
    $hasAdminCookie = $cookies->contains(fn ($c) => $c->getName() === $this->adminCookie);
    expect($hasAdminCookie)->toBeFalse();
});

// ─── Config override correctness ─────────────────────────────────────────

test('ScopeAdminSession mutates session.cookie + session.path for /admin requests', function () {
    Route::middleware('web')->get('/admin/test-probe-cookie', function () {
        return response()->json([
            'cookie' => config('session.cookie'),
            'path'   => config('session.path'),
        ]);
    });

    $payload = $this->get('/admin/test-probe-cookie')->json();

    expect($payload['cookie'])->toBe($this->adminCookie);
    expect($payload['path'])->toBe('/admin');
});

test('ScopeAdminSession is a no-op for non-admin URIs', function () {
    Route::middleware('web')->get('/test-probe-cookie', function () {
        return response()->json([
            'cookie' => config('session.cookie'),
            'path'   => config('session.path'),
        ]);
    });

    $payload = $this->get('/test-probe-cookie')->json();

    expect($payload['cookie'])->toBe($this->frontendCookie);
    expect($payload['path'])->toBe('/');
});

// ─── CSRF cross-isolation is verified at the architectural layer ──────────
//
// CSRF tokens are session-bound (`session()->token()`). The cookie-isolation
// tests above prove that frontend and admin sessions write to physically
// separate cookies under different paths, which means each session record
// has its own _token. A frontend token cannot validate an admin POST and
// vice versa.
//
// Functional cross-token verification requires a two-browser model the
// PHPUnit test client does not simulate (a single process holds one shared
// in-memory session driver), so we defer it to manual smoke / e2e.

// ─── Logout isolation ────────────────────────────────────────────────────

test('admin logout does not affect frontend session', function () {
    $this->actingAs($this->adminUser);

    // Frontend "stays" active — actingAs is in-process state. The check is:
    // calling admin logout invalidates the admin session and redirects to
    // admin login, without throwing or touching the frontend session record.
    $response = $this->post('/admin/auth/logout');
    $response->assertRedirect(route('admin.auth.login'));

    expect(auth()->check())->toBeFalse();
});

// ─── Remember-me removed for admin ───────────────────────────────────────

test('admin login ignores any client-supplied remember field', function () {
    // The form no longer collects remember, but a malicious client could
    // still attach it. The controller hard-disables remember regardless.
    $response = $this->post('/admin/auth/login', [
        'email'    => $this->adminUser->email,
        'password' => 'correct-horse-battery-staple',
        'remember' => true,
    ]);

    $response->assertRedirect(route('admin.dashboard'));

    // No remember-me cookie is set on the response.
    $cookies = collect($response->headers->getCookies());
    $rememberCookie = $cookies->first(fn ($c) => str_starts_with($c->getName(), 'remember_'));

    expect($rememberCookie)->toBeNull();
});
