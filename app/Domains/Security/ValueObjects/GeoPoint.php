<?php

declare(strict_types=1);

namespace App\Domains\Security\ValueObjects;

readonly class GeoPoint
{
    public function __construct(
        public Latitude $latitude,
        public Longitude $longitude
    ) {}

    public function distanceTo(self $other): float
    {
        $earthRadius = 6371;

        $lat1 = $this->latitude->value;
        $lon1 = $this->longitude->value;
        $lat2 = $other->latitude->value;
        $lon2 = $other->longitude->value;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public static function fromFloats(float $lat, float $lon): self
    {
        return new self(new Latitude($lat), new Longitude($lon));
    }
}
