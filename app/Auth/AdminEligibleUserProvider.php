<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * User provider that only resolves users with `access_admin_panel`.
 *
 * Backs the `admins` password broker so that password-reset attempts for
 * frontend-only accounts yield INVALID_USER from Password::broker('admins'),
 * which our admin reset controllers translate to the same success message
 * as a successful send — preventing user enumeration.
 *
 * This provider is NOT used by the `web` guard. Admin login goes through
 * the normal `web` guard with a permission check in the controller, so
 * admin users who are simultaneously frontend users still get a single
 * session.
 */
class AdminEligibleUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $user = parent::retrieveByCredentials($credentials);

        return $this->isAdminEligible($user) ? $user : null;
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $user = parent::retrieveById($identifier);

        return $this->isAdminEligible($user) ? $user : null;
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        $user = parent::retrieveByToken($identifier, $token);

        return $this->isAdminEligible($user) ? $user : null;
    }

    private function isAdminEligible(?Authenticatable $user): bool
    {
        return $user instanceof User && $user->canAccessAdminPanel();
    }
}
