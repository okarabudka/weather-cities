<?php

declare(strict_types=1);

namespace WeatherCities\Weather;

/** The provider rejected the API key; every further request would fail the same way. */
final class InvalidApiKey extends WeatherUnavailable
{
}
