<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
});

test('SuperAdminSeeder is idempotent and creates exactly one super admin', function () {
    config(['auth.super_admin_email' => 'ops@example.com']);

    expect(User::where('email', 'ops@example.com')->count())->toBe(0);

    // First run — creates the user.
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperAdminSeeder']);

    $user = User::where('email', 'ops@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('super_admin'))->toBeTrue();

    $hashBefore = $user->password;
    $createdAt  = $user->created_at;

    // Second run — must not create a duplicate, must not reset password,
    // must not lose or duplicate the super_admin role.
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperAdminSeeder']);

    expect(User::where('email', 'ops@example.com')->count())->toBe(1);

    $fresh = User::where('email', 'ops@example.com')->first();
    expect($fresh->password)->toBe($hashBefore);                            // password not regenerated
    expect((string) $fresh->created_at)->toBe((string) $createdAt);         // not recreated
    expect($fresh->roles->where('name', 'super_admin')->count())->toBe(1);  // role attached exactly once
});

test('SuperAdminSeeder is a no-op when SUPER_ADMIN_EMAIL is not configured', function () {
    config(['auth.super_admin_email' => null]);
    putenv('SUPER_ADMIN_EMAIL');

    $userCountBefore = User::count();

    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperAdminSeeder']);

    expect(User::count())->toBe($userCountBefore);
});

test('SuperAdminSeeder attaches super_admin role even if user already exists', function () {
    // Pre-existing user — not yet a super admin.
    $user = User::factory()->create([
        'email'             => 'preexisting-admin@example.com',
        'email_verified_at' => now(),
    ]);
    expect($user->hasRole('super_admin'))->toBeFalse();

    config(['auth.super_admin_email' => 'preexisting-admin@example.com']);

    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperAdminSeeder']);

    expect($user->fresh()->hasRole('super_admin'))->toBeTrue();
    // And nothing else got duplicated.
    expect(User::where('email', 'preexisting-admin@example.com')->count())->toBe(1);
});
