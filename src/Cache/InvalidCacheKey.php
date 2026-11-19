<?php

declare(strict_types=1);

namespace WeatherCities\Cache;

use Psr\SimpleCache\InvalidArgumentException;

final class InvalidCacheKey extends \InvalidArgumentException implements InvalidArgumentException
{
}
