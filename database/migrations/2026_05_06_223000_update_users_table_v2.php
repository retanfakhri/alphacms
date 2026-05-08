<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Essential
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('phone_code')->nullable()->after('phone');
            $table->timestamp('phone_verified_at')->nullable()->after('phone_code');

            // Security
            $table->boolean('local_password_enabled')->default(true)->after('password');
            $table->timestamp('password_changed_at')->nullable()->after('local_password_enabled');

            // Sessions & Activity
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamp('last_active_at')->nullable();

            // Profile
            $table->text('bio')->nullable();

            // Settings
            $table->boolean('has_whatsapp')->default(false);
            $table->boolean('comments_blocked')->default(false);
            $table->boolean('is_private')->default(false);
            $table->boolean('is_active')->default(true);

            // Account Status
            $table->string('account_status')->default('active'); // Linked to AccountStatus Enum
            $table->timestamp('banned_at')->nullable();
            $table->string('ban_reason')->nullable();

            // Social URLs
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('telegram_url')->nullable();
            $table->string('x_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('website_url')->nullable();

            // Social Login
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();

            // Soft Deletes
            $table->softDeletes();
            
            // Optimization
            $table->index(['provider', 'provider_id']);
            $table->index('account_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'username', 'phone', 'phone_code', 'phone_verified_at',
                'local_password_enabled', 'password_changed_at',
                'last_login_at', 'last_login_ip', 'last_active_at',
                'bio', 'has_whatsapp', 'comments_blocked', 'is_private', 'is_active',
                'account_status', 'banned_at', 'ban_reason',
                'facebook_url', 'instagram_url', 'tiktok_url', 'linkedin_url', 
                'telegram_url', 'x_url', 'youtube_url', 'website_url',
                'provider', 'provider_id'
            ]);
        });
    }
};
