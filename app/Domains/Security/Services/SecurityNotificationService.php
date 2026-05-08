<?php

declare(strict_types=1);

namespace App\Domains\Security\Services;

use App\Models\User;
use App\Models\UserSession;
use App\Domains\Security\ValueObjects\RiskScore;

class SecurityNotificationService
{
    public function notifySuspiciousLogin(User $user, UserSession $session, RiskScore $risk): void
    {
        // $user->notify(new \App\Domains\Security\Notifications\SuspiciousLoginDetected($session, $risk));
    }

    public function notifyNewDevice(User $user, UserSession $session): void
    {
        // $user->notify(new \App\Domains\Security\Notifications\NewDeviceDetected($session));
    }
}
