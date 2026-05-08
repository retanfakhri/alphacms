<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Find existing admin by username or email
        $admin = User::where('username', 'admin')
            ->orWhere('email', 'admin@admin.com')
            ->orWhere('email', 'arrmjo@gmail.com')
            ->first();

        if ($admin) {
            $admin->update([
                'user_type' => 'admin',
                'is_active' => true,
                'account_status' => \App\Enums\AccountStatus::Active,
            ]);
        } else {
            $admin = User::create([
                'name' => 'Super Admin',
                'username' => 'admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'account_status' => \App\Enums\AccountStatus::Active,
            ]);
        }

        // Give all permissions explicitly for the 'admin' guard
        // We pass the objects directly to avoid guard mismatch during name lookup
        $permissions = Permission::where('guard_name', 'admin')->get();
        $admin->syncPermissions($permissions);

        $this->command->info('Admin user "' . $admin->email . '" setup with all permissions!');
    }
}
