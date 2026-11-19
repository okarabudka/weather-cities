<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeatherCities\Domain\Coordinates;
use WeatherCities\Domain\InvalidCoordinates;

final class CoordinatesTest extends TestCase
{
    /**
     * @return iterable<string, array{float, float}>
     */
    public static function validProvider(): iterable
    {
        yield 'south-west corner' => [-90.0, -180.0];
        yield 'north-east corner' => [90.0, 180.0];
        yield 'origin' => [0.0, 0.0];
    }

    #[DataProvider('validProvider')]
    public function testAcceptsBoundaryValues(float $latitude, float $longitude): void
    {
        $coordinates = new Coordinates($latitude, $longitude);

        self::assertSame($latitude, $coordinates->latitude);
        self::assertSame($longitude, $coordinates->longitude);
    }

    /**
     * @return iterable<string, array{float, float}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'latitude above 90' => [90.0001, 0.0];
        yield 'latitude below -90' => [-90.0001, 0.0];
        yield 'longitude above 180' => [0.0, 180.0001];
        yield 'longitude below -180' => [0.0, -180.0001];
        yield 'NaN latitude' => [NAN, 0.0];
        yield 'infinite longitude' => [0.0, INF];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsOutOfRangeValues(float $latitude, float $longitude): void
    {
        $this->expectException(InvalidCoordinates::class);

        new Coordinates($latitude, $longitude);
    }
}
