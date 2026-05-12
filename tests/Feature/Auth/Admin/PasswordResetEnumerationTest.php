<?php

declare(strict_types=1);

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

    $this->adminUser = User::factory()->create([
        'email'             => 'admin@example.com',
        'password'          => Hash::make('original-pass-1234'),
        'is_active'         => true,
        'account_status'    => AccountStatus::Active,
        'email_verified_at' => now(),
    ]);
    $this->adminUser->assignRole('admin');

    $this->frontendOnlyUser = User::factory()->create([
        'email'             => 'reader@example.com',
        'password'          => Hash::make('original-pass-1234'),
        'is_active'         => true,
        'account_status'    => AccountStatus::Active,
        'email_verified_at' => now(),
    ]);
    $this->frontendOnlyUser->assignRole('user');
});

// ─── Forgot password — enumeration resistance ────────────────────────────

test('admin user receives a reset notification', function () {
    Notification::fake();

    $response = $this->post('/admin/auth/forgot-password', [
        'email' => $this->adminUser->email,
    ]);

    $response->assertSessionHas('status');
    Notification::assertSentTo($this->adminUser, ResetPassword::class);
});

test('frontend-only user receives the same status but NO notification', function () {
    Notification::fake();

    $response = $this->post('/admin/auth/forgot-password', [
        'email' => $this->frontendOnlyUser->email,
    ]);

    $response->assertSessionHas('status');
    Notification::assertNothingSent();
});

test('unknown email receives the same status but NO notification', function () {
    Notification::fake();

    $response = $this->post('/admin/auth/forgot-password', [
        'email' => 'nobody@example.com',
    ]);

    $response->assertSessionHas('status');
    Notification::assertNothingSent();
});

test('all three flows produce indistinguishable status responses', function () {
    Notification::fake();

    $adminResp    = $this->post('/admin/auth/forgot-password', ['email' => $this->adminUser->email]);
    session()->flush();

    $frontendResp = $this->post('/admin/auth/forgot-password', ['email' => $this->frontendOnlyUser->email]);
    session()->flush();

    $unknownResp  = $this->post('/admin/auth/forgot-password', ['email' => 'nobody@example.com']);

    expect($adminResp->status())->toBe($frontendResp->status());
    expect($adminResp->status())->toBe($unknownResp->status());
});

// ─── Reset password — broker isolation ───────────────────────────────────

test('admin can reset their password using an admin-broker token end-to-end', function () {
    $token = Password::broker('admins')->createToken($this->adminUser);

    $response = $this->post('/admin/auth/reset-password', [
        'token'                 => $token,
        'email'                 => $this->adminUser->email,
        'password'              => 'new-secure-pass-9876',
        'password_confirmation' => 'new-secure-pass-9876',
    ]);

    $response->assertRedirect(route('admin.auth.login'));
    $response->assertSessionHas('status');

    $this->adminUser->refresh();
    expect(Hash::check('new-secure-pass-9876', $this->adminUser->password))->toBeTrue();
});

test('frontend-only user cannot reset via the admin broker even with a valid frontend token', function () {
    // Token minted by the FRONTEND broker for a non-admin user.
    $frontendToken = Password::broker('users')->createToken($this->frontendOnlyUser);

    $response = $this->post('/admin/auth/reset-password', [
        'token'                 => $frontendToken,
        'email'                 => $this->frontendOnlyUser->email,
        'password'              => 'new-pass-1111',
        'password_confirmation' => 'new-pass-1111',
    ]);

    // Admin broker rejects: user isn't admin-eligible → INVALID_USER.
    $response->assertSessionHasErrors('email');

    $this->frontendOnlyUser->refresh();
    expect(Hash::check('original-pass-1234', $this->frontendOnlyUser->password))->toBeTrue();
});

test('reset with invalid token returns a generic error', function () {
    $response = $this->post('/admin/auth/reset-password', [
        'token'                 => 'totally-bogus-token',
        'email'                 => $this->adminUser->email,
        'password'              => 'new-pass-1111',
        'password_confirmation' => 'new-pass-1111',
    ]);

    $response->assertSessionHasErrors('email');

    $this->adminUser->refresh();
    expect(Hash::check('original-pass-1234', $this->adminUser->password))->toBeTrue();
});
