<?php

declare(strict_types=1);

namespace App\Enums;

enum TrustedProvider: string
{
    case Google = 'google';
    case Facebook = 'facebook';
    case Apple = 'apple';

    public static function isTrusted(string $provider): bool
    {
        return collect(self::cases())
            ->contains(fn (self $case) => $case->value === strtolower($provider));
    }
}
