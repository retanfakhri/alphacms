<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. تعريف كافة الصلاحيات بشكل صريح
        $permissions = [
            'access_admin_panel',
            'manage_users',
            'manage_roles',
            'manage_permissions',
            'manage_settings',
            'manage_content',
            'create_articles',
            'edit_articles',
            'publish_articles',
            'delete_articles',
            'create_news',
            'edit_news',
            'publish_news',
            'delete_news',
            'manage_general_settings',
            'manage_smtp_settings',
            'manage_social_settings',
            'manage_tracking_settings',
            'manage_media_settings',
            'manage_third_party_settings',
            'manage_ai_settings',
            'manage_cdn_settings',
            'flush_settings_cache',
            'moderate_comments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. إعداد الأدوار
        
        // Super Admin: يملك كل الصلاحيات في قاعدة البيانات + Gate bypass
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // Admin: site-wide management excluding sensitive integrations (SMTP, CDN, third-party, AI)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'access_admin_panel',
            'manage_users',
            'manage_content',
            'moderate_comments',
            'manage_general_settings',
            'manage_social_settings',
            'manage_tracking_settings',
            'manage_media_settings',
            'create_articles',
            'edit_articles',
            'publish_articles',
            'delete_articles',
            'create_news',
            'edit_news',
            'publish_news',
            'delete_news',
        ]);

        // Writer
        $writerRole = Role::firstOrCreate(['name' => 'writer', 'guard_name' => 'web']);
        $writerRole->syncPermissions([
            'access_admin_panel',
            'create_articles',
            'edit_articles',
            'delete_articles',
            'create_news',
            'edit_news',
            'delete_news',
        ]);

        // Regular User
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }
}
