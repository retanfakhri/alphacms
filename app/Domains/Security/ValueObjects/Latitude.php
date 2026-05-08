<?php

declare(strict_types=1);

namespace App\Domains\Security\ValueObjects;

use InvalidArgumentException;

readonly class Latitude
{
    public function __construct(public float $value)
    {
        if ($value < -90 || $value > 90) {
            throw new InvalidArgumentException("Latitude must be between -90 and 90.");
        }
    }

    public function __toString(): string
    {
        return number_format($this->value, 6, '.', '');
    }
}
