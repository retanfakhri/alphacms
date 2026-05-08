<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
class LoginListener
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        
        if ($user instanceof \App\Models\User) {
            $trackingData = \App\DTOs\SecurityTrackingData::fromRequest(request());
            
            \App\Domains\Auth\Events\UserLoggedIn::dispatch($user, $trackingData);
        }
    }
}
