<?php

declare(strict_types=1);

namespace WeatherCities\Domain;

/** Temperatures in °C. */
final readonly class WeatherData
{
    public function __construct(
        public float $tempMin,
        public float $tempMax,
        public float $tempCurrent,
        public string $description,
        public string $icon,
    ) {
    }

    public function spread(): float
    {
        return $this->tempMax - $this->tempMin;
    }
}
