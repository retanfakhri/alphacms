<?php

declare(strict_types=1);

namespace App\Domains\Security\Services;

use App\Models\User;
use App\Models\UserSession;
use App\Domains\Security\ValueObjects\RiskScore;
use App\Domains\Security\Events\HighRiskLoginDetected;

use App\Domains\Security\Actions\PromoteTrustedDevice;
use App\Domains\Security\Actions\QuarantineSession;

class SecurityOrchestrator
{
    public function __construct(
        protected SecurityNotificationService $notificationService,
        protected PromoteTrustedDevice $promoteAction,
        protected QuarantineSession $quarantineAction,
    ) {}

    public function handleLoginSecurity(User $user, UserSession $session, RiskScore $risk): void
    {
        if ($risk->isHigh()) {
            HighRiskLoginDetected::dispatch($user, $session, $risk);
            
            $this->notificationService->notifySuspiciousLogin($user, $session, $risk);

            if ($risk->isCritical()) {
                $this->quarantineAction->execute($session);
            }
        }
    }

    public function promoteToTrusted(UserSession $session): void
    {
        $this->promoteAction->execute($session);
    }
}
