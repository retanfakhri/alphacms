<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update users for personalization
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_article_read_at')->nullable();
            $table->json('reading_preferences')->nullable()
                ->comment('AI personalization and recommendation profile');
        });

        // 2. Update user_sessions for trusted devices
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->string('trusted_device_hash')->nullable()->after('session_fingerprint');
            $table->boolean('is_trusted_device')->default(false)->after('trusted_device_hash');
            $table->index('trusted_device_hash');
            $table->index('is_trusted_device');
        });

        // 3. Optimize user_locations index
        Schema::table('user_locations', function (Blueprint $table) {
            $table->index(['user_id', 'ip_address'], 'user_ip_composite_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_article_read_at', 'reading_preferences']);
        });

        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropColumn(['trusted_device_hash', 'is_trusted_device']);
        });

        Schema::table('user_locations', function (Blueprint $table) {
            $table->dropIndex('user_ip_composite_index');
        });
    }
};
