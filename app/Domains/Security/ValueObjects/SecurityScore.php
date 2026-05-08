<?php

declare(strict_types=1);

namespace App\Domains\Security\ValueObjects;

use InvalidArgumentException;

readonly class SecurityScore
{
    public function __construct(public int $value)
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException("Security score must be between 0 and 100.");
        }
    }

    public function isWeak(): bool
    {
        return $this->value < 40;
    }

    public function isExcellent(): bool
    {
        return $this->value >= 90;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
