<?php

declare(strict_types=1);

namespace App\Domains\Geo\ValueObjects;

use InvalidArgumentException;

use JsonSerializable;

readonly class Latitude implements JsonSerializable
{
    public function __construct(private float $value)
    {
        if ($value < -90 || $value > 90) {
            throw new InvalidArgumentException("Latitude must be between -90 and 90.");
        }
    }

    public function value(): float
    {
        return $this->value;
    }

    public function toFloat(): float
    {
        return round($this->value, 6);
    }

    public function jsonSerialize(): float
    {
        return $this->toFloat();
    }

    public function __toString(): string
    {
        return number_format($this->value, 6, '.', '');
    }
}
