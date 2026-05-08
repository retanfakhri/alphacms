<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Setup 'web' guard roles (Writer & User)
        // User wants these to have NO permissions assigned here.
        $webGuard = 'web';
        
        Role::firstOrCreate(['name' => 'writer', 'guard_name' => $webGuard]);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => $webGuard]);

        // 2. Setup 'admin' guard (Administrative only)
        $adminGuard = 'admin';

        // Delete 'writer' and 'user' roles from 'admin' guard if they exist
        Role::where('guard_name', $adminGuard)->whereIn('name', ['writer', 'user'])->delete();

        // Ensure the 'admin' role exists for 'admin' guard
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $adminGuard]);

        // Basic administrative permissions list
        $adminPermissions = [
            'access dashboard',
            'manage users',
            'manage settings',
            'create articles',
            'edit articles',
            'delete articles',
            'publish articles',
            'manage own articles',
            'upload media',
        ];

        foreach ($adminPermissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => $adminGuard]);
        }

        // Admin role gets all admin guard permissions
        $adminRole->syncPermissions(Permission::where('guard_name', $adminGuard)->get());
    }
}
