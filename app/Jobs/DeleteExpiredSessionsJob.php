<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\UserSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteExpiredSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Cleanup inactive sessions older than 90 days to prevent table bloat
        UserSession::where('is_active', false)
            ->where('updated_at', '<=', now()->subDays(90))
            ->delete();

        // Also cleanup old active sessions that haven't been touched in 30 days (likely dead sessions)
        UserSession::where('is_active', true)
            ->where('last_activity_at', '<=', now()->subDays(30))
            ->update([
                'is_active' => false,
                'logout_reason' => 'session_expired'
            ]);
    }
}
