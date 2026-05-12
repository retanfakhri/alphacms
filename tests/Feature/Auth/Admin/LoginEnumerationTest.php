<?php

declare(strict_types=1);

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

    // The admin login limiter persists across tests via the cache; flush it so
    // a noisy neighbour test doesn't lock us out.
    Cache::flush();

    $this->adminUser = User::factory()->create([
        'email'             => 'admin@example.com',
        'password'          => Hash::make('correct-horse-battery-staple'),
        'is_active'         => true,
        'account_status'    => AccountStatus::Active,
        'email_verified_at' => now(),
    ]);
    $this->adminUser->assignRole('admin');

    $this->frontendOnlyUser = User::factory()->create([
        'email'             => 'reader@example.com',
        'password'          => Hash::make('correct-horse-battery-staple'),
        'is_active'         => true,
        'account_status'    => AccountStatus::Active,
        'email_verified_at' => now(),
    ]);
    $this->frontendOnlyUser->assignRole('user');
});

// ─── Enumeration resistance ──────────────────────────────────────────────

test('unknown email and wrong-password admin both produce the same response shape', function () {
    $unknown = $this->post('/admin/auth/login', [
        'email'    => 'nobody@example.com',
        'password' => 'whatever',
    ]);
    $unknown->assertSessionHasErrors('email');
    $unknownErrors = session('errors')->getBag('default')->messages();

    Cache::flush(); // separate limiter buckets so the second attempt isn't throttled
    session()->flush();

    $wrongPassword = $this->post('/admin/auth/login', [
        'email'    => $this->adminUser->email,
        'password' => 'wrong-password',
    ]);
    $wrongPassword->assertSessionHasErrors('email');
    $wrongErrors = session('errors')->getBag('default')->messages();

    expect($unknown->status())->toBe($wrongPassword->status());
    expect($unknownErrors)->toBe($wrongErrors);
    expect(auth()->check())->toBeFalse();
});

test('valid credentials without admin access yield the same error as invalid credentials', function () {
    $denied = $this->post('/admin/auth/login', [
        'email'    => $this->frontendOnlyUser->email,
        'password' => 'correct-horse-battery-staple',
    ]);
    $denied->assertSessionHasErrors('email');
    $deniedErrors = session('errors')->getBag('default')->messages();

    Cache::flush();
    session()->flush();

    $invalid = $this->post('/admin/auth/login', [
        'email'    => 'nobody@example.com',
        'password' => 'whatever',
    ]);
    $invalid->assertSessionHasErrors('email');
    $invalidErrors = session('errors')->getBag('default')->messages();

    expect($denied->status())->toBe($invalid->status());
    expect($deniedErrors)->toBe($invalidErrors);
    expect(auth()->check())->toBeFalse();
    expect(auth()->user())->toBeNull();
});

// ─── Successful login ────────────────────────────────────────────────────

test('admin with valid credentials and access_admin_panel can log in', function () {
    $response = $this->post('/admin/auth/login', [
        'email'    => $this->adminUser->email,
        'password' => 'correct-horse-battery-staple',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($this->adminUser->id);
});

test('session id is regenerated on successful admin login (fixation prevention)', function () {
    $this->startSession();
    $idBefore = session()->getId();

    $this->post('/admin/auth/login', [
        'email'    => $this->adminUser->email,
        'password' => 'correct-horse-battery-staple',
    ]);

    expect(session()->getId())->not->toBe($idBefore);
});

// ─── Inactive / banned admin denied with same shape ─────────────────────

test('inactive admin cannot log in', function () {
    $this->adminUser->update(['is_active' => false]);

    $response = $this->post('/admin/auth/login', [
        'email'    => $this->adminUser->email,
        'password' => 'correct-horse-battery-staple',
    ]);

    $response->assertSessionHasErrors('email');
    expect(auth()->check())->toBeFalse();
});

test('banned admin cannot log in', function () {
    $this->adminUser->update([
        'account_status' => AccountStatus::Banned,
        'banned_at'      => now(),
    ]);

    $response = $this->post('/admin/auth/login', [
        'email'    => $this->adminUser->email,
        'password' => 'correct-horse-battery-staple',
    ]);

    $response->assertSessionHasErrors('email');
    expect(auth()->check())->toBeFalse();
});

// ─── Throttling ──────────────────────────────────────────────────────────

test('admin login is throttled after 3 failed attempts in a minute', function () {
    for ($i = 0; $i < 3; $i++) {
        $this->post('/admin/auth/login', [
            'email'    => $this->adminUser->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/admin/auth/login', [
        'email'    => $this->adminUser->email,
        'password' => 'correct-horse-battery-staple', // even with the right password now
    ]);

    $response->assertSessionHasErrors('email');
    $message = session('errors')->getBag('default')->first('email');

    // Assert it's the throttle message (not auth.failed) without binding to a
    // specific seconds count — the countdown ticks during the assertion window
    // and would otherwise make this test flaky (e.g. 60 vs 59).
    expect($message)->not->toBe(__('auth.failed'));

    // Build the set of acceptable throttle strings (60s and 59s — the only
    // two values availableIn can plausibly return during this assertion).
    $acceptable = [
        __('auth.throttle', ['seconds' => 60, 'minutes' => 1]),
        __('auth.throttle', ['seconds' => 59, 'minutes' => 1]),
    ];
    expect(in_array($message, $acceptable, true))->toBeTrue(
        "Expected throttle message, got: {$message}",
    );

    expect(auth()->check())->toBeFalse();
});

test('throttle bucket is keyed by email+ip (different admin emails are independent)', function () {
    // Burn the admin limiter for the admin user.
    for ($i = 0; $i < 3; $i++) {
        $this->post('/admin/auth/login', [
            'email'    => $this->adminUser->email,
            'password' => 'wrong-password',
        ]);
    }

    // A different admin email is NOT locked out — different bucket.
    $other = User::factory()->create([
        'email'             => 'other-admin@example.com',
        'password'          => Hash::make('correct-horse-battery-staple'),
        'is_active'         => true,
        'account_status'    => AccountStatus::Active,
        'email_verified_at' => now(),
    ]);
    $other->assignRole('admin');

    $response = $this->post('/admin/auth/login', [
        'email'    => $other->email,
        'password' => 'correct-horse-battery-staple',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
});

// ─── Logout ──────────────────────────────────────────────────────────────

test('admin logout invalidates session and redirects to login', function () {
    $this->actingAs($this->adminUser);

    $response = $this->post('/admin/auth/logout');

    $response->assertRedirect(route('admin.auth.login'));
    expect(auth()->check())->toBeFalse();
});
