<?php

declare(strict_types=1);

namespace App\Domains\Geo\Enums;

enum DistanceUnit: string
{
    case Kilometers = 'km';
    case Miles = 'mi';
    case Meters = 'm';

    public function multiplier(): float
    {
        return match ($this) {
            self::Kilometers => 1.0,
            self::Miles => 0.621371,
            self::Meters => 1000.0,
        };
    }
}
