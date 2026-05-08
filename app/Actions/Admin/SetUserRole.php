<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SetUserRole
{
    /**
     * Update the user's role.
     */
    public function execute(User $user, string $role): void
    {
        Validator::make(['role' => $role], [
            'role' => ['required', Rule::in(['admin', 'writer', 'user', 'editor'])],
        ])->validate();

        // SyncRoles removes all previous roles and assigns the new one
        $user->syncRoles([$role]);
    }
}
