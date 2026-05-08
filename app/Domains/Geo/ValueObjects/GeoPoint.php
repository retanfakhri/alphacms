<?php

declare(strict_types=1);

namespace App\Domains\Geo\ValueObjects;

use App\Domains\Geo\Enums\DistanceUnit;
use JsonSerializable;
use InvalidArgumentException;

readonly class GeoPoint implements JsonSerializable
{
    private const EARTH_RADIUS_KM = 6371.0;
    private const EPSILON = 0.000001;

    public function __construct(
        private Latitude $latitude,
        private Longitude $longitude
    ) {}

    public function latitude(): Latitude
    {
        return $this->latitude;
    }

    public function longitude(): Longitude
    {
        return $this->longitude;
    }

    public function distanceTo(self $other, DistanceUnit $unit = DistanceUnit::Kilometers): Distance
    {
        $lat1Rad = deg2rad($this->latitude->value());
        $lon1Rad = deg2rad($this->longitude->value());
        $lat2Rad = deg2rad($other->latitude->value());
        $lon2Rad = deg2rad($other->longitude->value());

        $dLat = $lat2Rad - $lat1Rad;
        $dLon = $lon2Rad - $lon1Rad;

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distanceInKm = self::EARTH_RADIUS_KM * $c;

        return (new Distance($distanceInKm, DistanceUnit::Kilometers))->to($unit);
    }

    public function equals(self $other): bool
    {
        return abs($this->latitude->value() - $other->latitude->value()) < self::EPSILON
            && abs($this->longitude->value() - $other->longitude->value()) < self::EPSILON;
    }

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude->value(),
            'longitude' => $this->longitude->value(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromFloats(float $lat, float $lon): self
    {
        return new self(new Latitude($lat), new Longitude($lon));
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['latitude'], $data['longitude'])) {
            throw new InvalidArgumentException("Array must contain 'latitude' and 'longitude' keys.");
        }

        return self::fromFloats((float) $data['latitude'], (float) $data['longitude']);
    }

    public static function fromString(string $coordinates): self
    {
        $parts = explode(',', $coordinates);
        
        if (count($parts) !== 2) {
            throw new InvalidArgumentException("String must be in format 'lat,lon'.");
        }

        return self::fromFloats((float) trim($parts[0]), (float) trim($parts[1]));
    }
}
