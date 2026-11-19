<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WeatherCities\Domain\City;
use WeatherCities\Domain\CityWeather;
use WeatherCities\Domain\CityWeatherSorter;
use WeatherCities\Domain\Coordinates;
use WeatherCities\Domain\WeatherData;

final class CityWeatherSorterTest extends TestCase
{
    public function testSortsBySpreadAscending(): void
    {
        $sorted = (new CityWeatherSorter())->bySpread([
            self::row('A', 5.0),
            self::row('B', 1.0),
            self::row('C', 3.0),
        ]);

        self::assertSame(['B', 'C', 'A'], self::names($sorted));
    }

    public function testEqualSpreadsKeepFileOrder(): void
    {
        $sorted = (new CityWeatherSorter())->bySpread([
            self::row('A', 2.0),
            self::row('B', 1.0),
            self::row('C', 2.0),
            self::row('D', 2.0),
        ]);

        self::assertSame(['B', 'A', 'C', 'D'], self::names($sorted));
    }

    public function testRowsWithoutWeatherGoLast(): void
    {
        $sorted = (new CityWeatherSorter())->bySpread([
            self::row('A', null),
            self::row('B', 4.0),
            self::row('C', null),
            self::row('D', 0.5),
        ]);

        self::assertSame(['D', 'B', 'A', 'C'], self::names($sorted));
    }

    private static function row(string $name, ?float $spread): CityWeather
    {
        $weather = $spread === null ? null : new WeatherData(10.0, 10.0 + $spread, 11.0, 'clear sky', '01d');

        return new CityWeather(new City('DE', '00000', $name, new Coordinates(50.0, 8.0)), $weather, null);
    }

    /**
     * @param list<CityWeather> $rows
     *
     * @return list<string>
     */
    private static function names(array $rows): array
    {
        return array_map(static fn (CityWeather $row): string => $row->city->name, $rows);
    }
}
