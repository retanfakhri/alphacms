<?php

declare(strict_types=1);

namespace App\Domains\Auth\Listeners;

use App\Domains\Auth\Events\UserLoggedIn;
use App\Domains\Security\Services\AuthTrackingService;

class TrackUserLogin
{
    public function __construct(
        protected AuthTrackingService $trackingService
    ) {}

    public function handle(UserLoggedIn $event): void
    {
        $this->trackingService->trackLogin($event->user, $event->trackingData);
    }
}
