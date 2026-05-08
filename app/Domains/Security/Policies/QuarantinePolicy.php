<?php

declare(strict_types=1);

namespace App\Domains\Security\Policies;

use App\Models\User;
use App\Models\UserSession;
use App\Domains\Security\ValueObjects\RiskScore;
use App\Domains\Security\Enums\QuarantineReason;

class QuarantinePolicy
{
    public function shouldQuarantine(User $user, UserSession $session, RiskScore $risk): ?QuarantineReason
    {
        if ($risk->isCritical()) {
            return QuarantineReason::HighRiskScore;
        }

        // Future: Check for impossible travel or blacklisted IPs
        return null;
    }
}
