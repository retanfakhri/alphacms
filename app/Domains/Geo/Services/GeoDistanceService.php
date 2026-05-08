<?php

declare(strict_types=1);

namespace App\Domains\Geo\Services;

use App\Domains\Geo\ValueObjects\GeoPoint;
use App\Domains\Geo\Enums\DistanceUnit;

use App\Domains\Geo\ValueObjects\Distance;

class GeoDistanceService
{
    public function calculate(
        GeoPoint $point1, 
        GeoPoint $point2, 
        DistanceUnit $unit = DistanceUnit::Kilometers
    ): Distance {
        return $point1->distanceTo($point2, $unit);
    }
}
