<?php

declare(strict_types=1);

namespace App\Events\User;

use App\Models\User;
use App\DTOs\OnboardingData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOnboardingCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public OnboardingData $onboardingData
    ) {}
}
