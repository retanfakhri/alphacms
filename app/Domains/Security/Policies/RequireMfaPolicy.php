<?php

declare(strict_types=1);

namespace App\Domains\Security\Policies;

use App\Models\User;
use App\Models\UserSession;
use App\Domains\Security\ValueObjects\RiskScore;

class RequireMfaPolicy
{
    public function shouldEnforce(User $user, UserSession $session, RiskScore $risk): bool
    {
        // Enforce MFA if risk is high and user hasn't verified this device yet
        return $risk->isHigh() && !$session->is_trusted_device;
    }
}
