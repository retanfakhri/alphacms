<?php

declare(strict_types=1);

namespace App\Domains\Auth\Events;

use App\Models\User;
use App\DTOs\SecurityTrackingData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public SecurityTrackingData $trackingData,
    ) {}
}
