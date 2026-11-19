<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WeatherCities\Domain\WeatherData;

final class WeatherDataTest extends TestCase
{
    public function testSpreadIsMaxMinusMin(): void
    {
        $weather = new WeatherData(-2.5, 4.0, 1.0, 'light snow', '13d');

        self::assertEqualsWithDelta(6.5, $weather->spread(), 1e-9);
    }
}
