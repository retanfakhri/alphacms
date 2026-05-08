<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_security_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('risk_score')->default(0);
            $table->integer('trusted_devices_count')->default(0);
            $table->timestamp('last_suspicious_activity_at')->nullable();
            $table->integer('login_anomalies_count')->default(0);
            $table->integer('security_score')->default(100);
            $table->boolean('mfa_enabled')->default(false);
            $table->timestamp('last_password_change_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_security_profiles');
    }
};
