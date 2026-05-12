<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Production-safe super admin bootstrap.
 *
 * Reads the operator email from `SUPER_ADMIN_EMAIL`. Creates the user with a
 * random 32-char password (never logged, never returned) so that the only way
 * to access the account is through the admin password reset flow.
 *
 * Idempotent: re-running this seeder will not reset the password or break the
 * existing account; it only ensures the `super_admin` role is attached.
 *
 * Usage in production:
 *   1. Set SUPER_ADMIN_EMAIL in .env (e.g. ops@yourdomain.com)
 *   2. php artisan db:seed --class=Database\\Seeders\\RolesAndPermissionsSeeder
 *   3. php artisan db:seed --class=Database\\Seeders\\SuperAdminSeeder
 *   4. Visit /admin/auth/forgot-password and set the password.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) (config('auth.super_admin_email') ?? env('SUPER_ADMIN_EMAIL', ''));

        if ($email === '') {
            $this->command?->warn('SUPER_ADMIN_EMAIL is not set; skipping SuperAdminSeeder.');
            return;
        }

        // Roles must exist before assignment.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => 'Super Admin',
                'username'          => 'super_admin_' . Str::random(6),
                'password'          => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'is_active'         => true,
                'account_status'    => AccountStatus::Active,
            ],
        );

        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }

        $this->command?->info(sprintf(
            'Super admin ready for %s. Trigger password reset at /admin/auth/forgot-password to set a password.',
            $email,
        ));
    }
}
