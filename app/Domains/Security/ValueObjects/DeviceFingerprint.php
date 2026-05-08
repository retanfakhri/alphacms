<?php

declare(strict_types=1);

namespace App\Domains\Security\ValueObjects;

readonly class DeviceFingerprint
{
    public function __construct(public string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException("Device fingerprint cannot be empty.");
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
