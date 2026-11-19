<?php

declare(strict_types=1);

namespace WeatherCities\Domain;

final class CityWeatherSorter
{
    /**
     * Ascending by temperature spread; rows without weather go last. usort is stable since PHP 8.0,
     * so equal spreads keep the order of the cities file.
     *
     * @param list<CityWeather> $rows
     *
     * @return list<CityWeather>
     */
    public function bySpread(array $rows): array
    {
        usort($rows, static function (CityWeather $a, CityWeather $b): int {
            if ($a->weather === null || $b->weather === null) {
                return ($a->weather === null) <=> ($b->weather === null);
            }

            return $a->weather->spread() <=> $b->weather->spread();
        });

        return $rows;
    }
}
