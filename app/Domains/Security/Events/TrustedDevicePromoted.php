<?php

declare(strict_types=1);

namespace App\Domains\Security\Events;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrustedDevicePromoted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public UserSession $session
    ) {}
}
