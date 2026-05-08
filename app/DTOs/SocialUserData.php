<?php

declare(strict_types=1);

namespace App\DTOs;

use Laravel\Socialite\Contracts\User as SocialiteUser;

readonly class SocialUserData
{
    public function __construct(
        public string $id,
        public string $provider,
        public ?string $email,
        public ?string $name = null,
        public ?string $nickname = null,
        public ?string $avatar = null,
    ) {}

    public static function fromSocialite(SocialiteUser $user, string $provider): self
    {
        return new self(
            id: (string) $user->getId(),
            provider: $provider,
            email: $user->getEmail(),
            name: $user->getName(),
            nickname: $user->getNickname(),
            avatar: $user->getAvatar(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'provider' => $this->provider,
        ];
    }
}
