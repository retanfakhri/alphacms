<?php

declare(strict_types=1);

namespace App\Domains\Security\ValueObjects;

use InvalidArgumentException;

readonly class RiskScore
{
    public function __construct(public int $value)
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException("Risk score must be between 0 and 100.");
        }
    }

    public function isLow(): bool
    {
        return $this->value < 10;
    }

    public function isHigh(): bool
    {
        return $this->value >= 50;
    }

    public function isCritical(): bool
    {
        return $this->value >= 80;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
