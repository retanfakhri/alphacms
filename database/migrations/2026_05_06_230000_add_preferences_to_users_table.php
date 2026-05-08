<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_locale')->nullable()->after('reading_preferences');
            $table->json('preferred_topics')->nullable()->after('preferred_locale');
            $table->json('notification_preferences')->nullable()->after('preferred_topics');
            $table->timestamp('onboarding_completed_at')->nullable()->after('notification_preferences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_locale',
                'preferred_topics',
                'notification_preferences',
                'onboarding_completed_at',
            ]);
        });
    }
};
