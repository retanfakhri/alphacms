<?php

declare(strict_types=1);

namespace App\Domains\Security\Actions;

use App\Models\UserSession;
use App\Domains\Security\Events\TrustedDevicePromoted;

class PromoteTrustedDevice
{
    public function execute(UserSession $session): void
    {
        $session->update(['is_trusted_device' => true]);
        
        TrustedDevicePromoted::dispatch($session->authenticatable, $session);
    }
}
