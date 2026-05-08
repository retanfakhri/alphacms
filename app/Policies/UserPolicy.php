<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Check if a user is the Super Admin.
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->email === 'admin@admin.com' || $user->id === 1;
    }

    public function viewAny(User $user): bool
    {
        return $user->user_type === 'admin';
    }

    public function view(User $user, User $model): bool
    {
        return $user->user_type === 'admin' || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'admin';
    }

    public function update(User $user, User $model): bool
    {
        // If target is Super Admin, only the Super Admin themselves can update
        if ($this->isSuperAdmin($model)) {
            return $user->id === $model->id;
        }

        // Admins can update anyone else
        if ($user->user_type === 'admin') {
            return true;
        }

        // Users can update their own profile
        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        // Super Admin cannot be deleted by anyone
        if ($this->isSuperAdmin($model)) {
            return false;
        }

        // Admins can delete others
        return $user->user_type === 'admin';
    }

    public function ban(User $user, User $model): bool
    {
        // Super Admin cannot be banned
        if ($this->isSuperAdmin($model)) {
            return false;
        }

        return $user->user_type === 'admin';
    }
}
