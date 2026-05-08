<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticlePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'writer']);
    }

    public function update(User $user, $article): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('writer')) {
            return $user->id === $article->user_id;
        }

        return false;
    }

    public function delete(User $user, $article): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('writer') && $user->id === $article->user_id);
    }

    public function publish(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
