<?php

declare(strict_types=1);

namespace App\Domains\Security\Policies;

use App\Models\User;
use App\Models\UserSession;

class GeoAnomalyPolicy
{
    public function isImpossibleTravel(User $user, UserSession $currentSession): bool
    {
        $lastSession = UserSession::where('authenticatable_id', $user->id)
            ->where('id', '!=', $currentSession->id)
            ->latest('logged_in_at')
            ->first();

        if (!$lastSession || !$lastSession->location || !$currentSession->location) {
            return false;
        }

        // Future: Calculate distance and time difference
        return false; 
    }
}
