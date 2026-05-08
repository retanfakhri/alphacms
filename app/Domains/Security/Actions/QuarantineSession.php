<?php

declare(strict_types=1);

namespace App\Domains\Security\Actions;

use App\Models\UserSession;

use App\Domains\Security\Enums\QuarantineReason;

class QuarantineSession
{
    public function execute(UserSession $session, QuarantineReason $reason = QuarantineReason::HighRiskScore): void
    {
        $session->update([
            'is_quarantined' => true,
            'quarantined_at' => now(),
            'quarantine_reason' => $reason->value,
        ]);
    }
}
