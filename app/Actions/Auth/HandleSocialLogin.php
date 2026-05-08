<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Enums\AccountStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\DTOs\SocialUserData;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class HandleSocialLogin
{
    public function execute(SocialUserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::where('provider', $data->provider)
                ->where('provider_id', $data->id)
                ->first();

            if (!$user) {
                // Try matching by email
                $user = User::where('email', $data->email)->first();

                if ($user) {
                    // Security Check: Prevent account takeover
                    if (!$user->provider_id) {
                        $user->update([
                            'provider' => $data->provider,
                            'provider_id' => $data->id,
                            'email_verified_at' => $user->email_verified_at ?? ($this->isVerifiedProvider($data->provider) ? now() : null),
                        ]);
                    } else {
                        // Better UX for Enterprise Apps: Throw ValidationException instead of abort(403)
                        throw ValidationException::withMessages([
                            'email' => [__('This email is already linked to another login provider.')],
                        ]);
                    }
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $data->name ?? $data->nickname,
                        'username' => $this->generateUsername($data->nickname ?? $data->name),
                        'email' => $data->email,
                        'password' => null, 
                        'local_password_enabled' => false,
                        'provider' => $data->provider,
                        'provider_id' => $data->id,
                        // Selective email verification based on provider trust
                        'email_verified_at' => $this->isVerifiedProvider($data->provider) ? now() : null, 
                        'account_status' => AccountStatus::Active,
                    ]);

                    // Initial avatar
                    try {
                        if ($data->avatar) {
                            $user->addMediaFromUrl($data->avatar)
                                ->toMediaCollection('avatars');
                        }
                    } catch (\Exception $e) {}
                }
            }

            Auth::login($user, true);

            return $user;
        });
    }

    protected function isVerifiedProvider(string $provider): bool
    {
        // Only trust major providers that guarantee email verification
        return in_array(strtolower($provider), ['google', 'facebook', 'apple']);
    }

    protected function generateUsername(?string $name): string
    {
        $base = str($name ?? 'user')
            ->slug('_')
            ->limit(20, '')
            ->toString();

        do {
            $username = $base . '_' . random_int(1000, 999999);
        } while (User::where('username', $username)->exists());

        return $username;
    }
}
