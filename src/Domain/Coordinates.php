<?php

declare(strict_types=1);

namespace WeatherCities\Domain;

final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if (!is_finite($latitude) || $latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidCoordinates(sprintf('Latitude must be between -90 and 90, got %g.', $latitude));
        }
        if (!is_finite($longitude) || $longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidCoordinates(sprintf('Longitude must be between -180 and 180, got %g.', $longitude));
        }
    }
}
