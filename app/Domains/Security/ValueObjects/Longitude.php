<?php

declare(strict_types=1);

namespace App\Domains\Security\ValueObjects;

use InvalidArgumentException;

readonly class Longitude
{
    public function __construct(public float $value)
    {
        if ($value < -180 || $value > 180) {
            throw new InvalidArgumentException("Longitude must be between -180 and 180.");
        }
    }

    public function __toString(): string
    {
        return number_format($this->value, 6, '.', '');
    }
}
