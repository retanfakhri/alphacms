<?php

declare(strict_types=1);

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    Cache::flush();

    // Generate a real TOTP secret. Use Crypt::encrypt (serialized form) to
    // match Fortify's storage convention — TwoFactorChallengeController
    // decrypts via Fortify::currentEncrypter()->decrypt() which expects the
    // serialized payload that Crypt::encrypt() produces.
    $this->secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();

    $this->adminUser = User::factory()->create([
        'email'                       => 'admin@example.com',
        'password'                    => Hash::make('correct-horse-battery-staple'),
        'is_active'                   => true,
        'account_status'              => AccountStatus::Active,
        'email_verified_at'           => now(),
        'two_factor_secret'           => Crypt::encrypt($this->secret),
        'two_factor_recovery_codes'   => Crypt::encrypt(json_encode([
            'recovery-code-one-1111',
            'recovery-code-two-2222',
        ])),
        'two_factor_confirmed_at'     => now(),
    ]);
    $this->adminUser->assignRole('admin');
});

/**
 * Drive the admin password step so the admin session gets a real login.id
 * intent written through the ScopeAdminSession middleware path. The test
 * client preserves the admin cookie between requests, so the next request
 * to /admin/* will resolve the same session.
 */
function establishAdminLoginIntent($test, User $user): void
{
    $test->post('/admin/auth/login', [
        'email'    => $user->email,
        'password' => 'correct-horse-battery-staple',
    ]);
}

function currentTotp(string $secret): string
{
    return (new Google2FA())->getCurrentOtp($secret);
}

// ─── login → challenge handoff ──────────────────────────────────────────

test('login with 2FA-enabled admin redirects to admin 2FA challenge', function () {
    $response = $this->post('/admin/auth/login', [
        'email'    => $this->adminUser->email,
        'password' => 'correct-horse-battery-staple',
    ]);

    $response->assertRedirect(route('admin.auth.two-factor.login'));
    expect(auth()->check())->toBeFalse(); // not logged in yet
});

test('challenge page renders after a pending challenge is established', function () {
    establishAdminLoginIntent($this, $this->adminUser);

    $response = $this->get('/admin/auth/two-factor-challenge');
    $response->assertOk();
});

test('challenge page redirects to admin login when no pending challenge', function () {
    $response = $this->get('/admin/auth/two-factor-challenge');
    $response->assertRedirect(route('admin.auth.login'));
});

// ─── TOTP verification (success) ────────────────────────────────────────

test('valid TOTP code completes admin login and lands on dashboard', function () {
    establishAdminLoginIntent($this, $this->adminUser);

    $response = $this->post('/admin/auth/two-factor-challenge', [
        'code' => currentTotp($this->secret),
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($this->adminUser->id);
});

// ─── Invalid code ────────────────────────────────────────────────────────

test('invalid TOTP code keeps user unauthenticated and shows error', function () {
    establishAdminLoginIntent($this, $this->adminUser);

    $response = $this->post('/admin/auth/two-factor-challenge', ['code' => '000000']);

    $response->assertSessionHasErrors('code');
    expect(auth()->check())->toBeFalse();
});

// ─── Recovery code (success + consumption) ──────────────────────────────

test('valid recovery code completes login and consumes the code', function () {
    establishAdminLoginIntent($this, $this->adminUser);

    $response = $this->post('/admin/auth/two-factor-challenge', [
        'recovery_code' => 'recovery-code-one-1111',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    expect(auth()->check())->toBeTrue();

    $remaining = json_decode(
        Crypt::decrypt($this->adminUser->fresh()->two_factor_recovery_codes),
        true,
    );
    expect($remaining)->not->toContain('recovery-code-one-1111');
    expect($remaining)->toContain('recovery-code-two-2222');
});

test('invalid recovery code rejected', function () {
    establishAdminLoginIntent($this, $this->adminUser);

    $response = $this->post('/admin/auth/two-factor-challenge', [
        'recovery_code' => 'not-a-real-recovery-code',
    ]);

    $response->assertSessionHasErrors('code');
    expect(auth()->check())->toBeFalse();
});

// ─── Permission revocation between password step and 2FA step ───────────

test('admin who lost access_admin_panel between steps is rejected', function () {
    establishAdminLoginIntent($this, $this->adminUser);

    // Simulate role removed by another admin in the meantime.
    $this->adminUser->syncRoles([]);

    $response = $this->post('/admin/auth/two-factor-challenge', [
        'code' => currentTotp($this->secret),
    ]);

    $response->assertSessionHasErrors('code');
    expect(auth()->check())->toBeFalse();
});

// ─── Intended-URL preservation ──────────────────────────────────────────

test('intended URL survives the password → 2FA → success sequence', function () {
    // Unauthenticated hit to a protected admin page records `url.intended`.
    $this->get('/admin/users');

    // Password step (sets login.id in the admin session).
    $this->post('/admin/auth/login', [
        'email'    => $this->adminUser->email,
        'password' => 'correct-horse-battery-staple',
    ]);

    // 2FA step.
    $response = $this->post('/admin/auth/two-factor-challenge', [
        'code' => currentTotp($this->secret),
    ]);

    $response->assertRedirect();
    $location = (string) $response->headers->get('Location');
    expect(Str::contains($location, '/admin/users'))->toBeTrue(
        "Expected redirect to /admin/users, got: {$location}",
    );
});

// ─── Remember-me is hard-disabled ───────────────────────────────────────

test('successful 2FA does not set a remember-me cookie', function () {
    establishAdminLoginIntent($this, $this->adminUser);

    $response = $this->post('/admin/auth/two-factor-challenge', [
        'code' => currentTotp($this->secret),
    ]);

    $rememberCookie = collect($response->headers->getCookies())
        ->first(fn ($c) => str_starts_with($c->getName(), 'remember_'));

    expect($rememberCookie)->toBeNull();
});
