<?php

declare(strict_types=1);

namespace WeatherCities\Weather;

use WeatherCities\Domain\Coordinates;
use WeatherCities\Domain\WeatherData;

/** Port: the application asks for weather through this interface only, so providers can be swapped in one place. */
interface WeatherProvider
{
    /**
     * @throws WeatherUnavailable
     */
    public function current(Coordinates $coordinates): WeatherData;
}
