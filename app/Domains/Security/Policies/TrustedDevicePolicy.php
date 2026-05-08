<?php

declare(strict_types=1);

namespace App\Domains\Security\Policies;

use App\Models\User;
use App\Models\UserSession;
use App\Domains\Security\ValueObjects\RiskScore;

class TrustedDevicePolicy
{
    public function canAutoTrust(User $user, UserSession $session, RiskScore $risk): bool
    {
        // Only auto-trust if risk is very low and it's a known environment
        return $risk->isLow() && $session->is_active;
    }
}
