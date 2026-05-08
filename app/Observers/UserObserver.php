<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AccountStatus;
use App\Models\User;

class UserObserver
{
    public function creating(User $user): void
    {
        // Default status
        if (is_null($user->account_status)) {
            $user->account_status = AccountStatus::Active;
        }

        // is_active default
        $user->is_active = $user->is_active ?? true;
    }

    public function created(User $user): void
    {
        // Role assignment removed from observer to allow flexible registration flows
        // (e.g. Writer vs User during signup)
    }
}
