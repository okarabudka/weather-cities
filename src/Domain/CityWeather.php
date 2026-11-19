<?php

declare(strict_types=1);

namespace WeatherCities\Domain;

/** One table row: a city with its weather, or without it when the provider failed for that city. */
final readonly class CityWeather
{
    public function __construct(
        public City $city,
        public ?WeatherData $weather,
        public ?float $distanceKm,
    ) {
    }
}
