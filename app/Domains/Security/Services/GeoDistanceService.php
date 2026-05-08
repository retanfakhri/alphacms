<?php

declare(strict_types=1);

namespace App\Domains\Security\Services;

use App\Domains\Security\ValueObjects\GeoPoint;

class GeoDistanceService
{
    /**
     * Calculate distance between two points in km using Haversine formula.
     */
    public function calculate(GeoPoint $point1, GeoPoint $point2): float
    {
        return $point1->distanceTo($point2);
    }
}
