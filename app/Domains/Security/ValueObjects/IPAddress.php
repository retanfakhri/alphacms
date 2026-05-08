<?php

declare(strict_types=1);

namespace App\Domains\Security\ValueObjects;

use InvalidArgumentException;

readonly class IPAddress
{
    public function __construct(public string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException("Invalid IP address: {$value}");
        }
    }

    public function isIpv4(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function isIpv6(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
