<?php

declare(strict_types=1);

namespace WeatherCities\Domain;

final readonly class City
{
    public function __construct(
        public string $country,
        // A string, because postal codes can have leading zeros.
        public string $zip,
        public string $name,
        public Coordinates $coordinates,
    ) {
    }
}
