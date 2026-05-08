<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->morphs('authenticatable', 'auth_index');
            $table->foreignId('location_id')->nullable()->constrained('user_locations')->onDelete('set null');
            $table->string('guard')->default('web');
            $table->string('session_id')->unique()->nullable();
            $table->string('login_type')->default('manual'); // Enum: LoginType
            $table->string('device_type')->default('desktop'); // Enum: DeviceType
            $table->string('device_name')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('session_fingerprint')->nullable();
            $table->integer('risk_score')->default(0);
            $table->timestamp('logged_in_at')->nullable();
            $table->timestamp('logged_out_at')->nullable();
            $table->string('logout_reason')->nullable(); // Enum: LogoutReason
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['authenticatable_id', 'authenticatable_type']);
            $table->index('session_id');
            $table->index('is_active');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
