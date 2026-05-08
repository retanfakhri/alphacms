<?php

declare(strict_types=1);

namespace App\Domains\Geo\ValueObjects;

use App\Domains\Geo\Enums\DistanceUnit;
use JsonSerializable;

readonly class Distance implements JsonSerializable
{
    public function __construct(
        private float $value,
        private DistanceUnit $unit = DistanceUnit::Kilometers
    ) {}

    public function value(): float
    {
        return $this->value;
    }

    public function unit(): DistanceUnit
    {
        return $this->unit;
    }

    public function to(DistanceUnit $newUnit): self
    {
        if ($this->unit === $newUnit) {
            return $this;
        }

        // Convert to KM first (base unit)
        $valueInKm = $this->value / $this->unit->multiplier();
        
        // Convert to new unit
        return new self($valueInKm * $newUnit->multiplier(), $newUnit);
    }

    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unit->value,
            'formatted' => (string) $this,
        ];
    }

    public function __toString(): string
    {
        return number_format($this->value, 2, '.', '') . ' ' . $this->unit->value;
    }
}
