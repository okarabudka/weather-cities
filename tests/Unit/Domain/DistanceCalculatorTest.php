<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WeatherCities\Domain\Coordinates;
use WeatherCities\Domain\DistanceCalculator;

final class DistanceCalculatorTest extends TestCase
{
    // Coordinates from data/cities.dat.
    private const float BERLIN_LAT = 52.5075419;
    private const float BERLIN_LON = 13.4251364;
    private const float MAGDEBURG_LAT = 52.1312151;
    private const float MAGDEBURG_LON = 11.6222587;

    public function testSamePointIsZero(): void
    {
        $berlin = new Coordinates(self::BERLIN_LAT, self::BERLIN_LON);

        self::assertEqualsWithDelta(0.0, (new DistanceCalculator())->km($berlin, $berlin), 1e-9);
    }

    public function testBerlinToMagdeburgIsAbout130Km(): void
    {
        $distance = (new DistanceCalculator())->km(
            new Coordinates(self::BERLIN_LAT, self::BERLIN_LON),
            new Coordinates(self::MAGDEBURG_LAT, self::MAGDEBURG_LON),
        );

        self::assertEqualsWithDelta(130.0, $distance, 3.0);
    }

    public function testIsSymmetric(): void
    {
        $calculator = new DistanceCalculator();
        $berlin = new Coordinates(self::BERLIN_LAT, self::BERLIN_LON);
        $magdeburg = new Coordinates(self::MAGDEBURG_LAT, self::MAGDEBURG_LON);

        self::assertEqualsWithDelta($calculator->km($berlin, $magdeburg), $calculator->km($magdeburg, $berlin), 1e-9);
    }

    public function testAntipodesAreHalfTheCircumference(): void
    {
        $distance = (new DistanceCalculator())->km(new Coordinates(0.0, 0.0), new Coordinates(0.0, 180.0));

        self::assertEqualsWithDelta(M_PI * 6371.0, $distance, 1e-6);
    }
}
