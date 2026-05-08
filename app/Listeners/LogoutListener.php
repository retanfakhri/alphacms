<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\LogoutReason;
use App\Models\UserSession;
use Illuminate\Auth\Events\Logout;

class LogoutListener
{
    public function handle(Logout $event): void
    {
        if ($event->user) {
            UserSession::where('authenticatable_id', $event->user->id)
                ->where('session_id', session()->getId())
                ->update([
                    'is_active' => false,
                    'logged_out_at' => now(),
                    'logout_reason' => LogoutReason::UserRequest,
                ]);
        }
    }
}
