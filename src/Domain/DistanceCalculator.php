<?php

declare(strict_types=1);

namespace WeatherCities\Domain;

/** Great-circle distance with the haversine formula. */
final class DistanceCalculator
{
    private const float EARTH_RADIUS_KM = 6371.0;

    public function km(Coordinates $from, Coordinates $to): float
    {
        $lat1 = deg2rad($from->latitude);
        $lat2 = deg2rad($to->latitude);
        $deltaLat = $lat2 - $lat1;
        $deltaLon = deg2rad($to->longitude - $from->longitude);

        $a = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));
    }
}
