<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Models\User;
use App\DTOs\SecurityTrackingData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuspiciousLoginDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public SecurityTrackingData $securityData,
        public string $reason
    ) {}
}
