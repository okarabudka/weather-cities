<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Weather;

use PHPUnit\Framework\TestCase;
use WeatherCities\Cache\FilesystemCache;
use WeatherCities\Domain\Coordinates;
use WeatherCities\Domain\WeatherData;
use WeatherCities\Tests\Support\FrozenClock;
use WeatherCities\Tests\Support\TemporaryDirectory;
use WeatherCities\Weather\CachedWeatherProvider;
use WeatherCities\Weather\WeatherProvider;
use WeatherCities\Weather\WeatherUnavailable;

final class CachedWeatherProviderTest extends TestCase
{
    private const int TTL = 600;

    private string $directory;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->directory = TemporaryDirectory::create();
        $this->clock = new FrozenClock();
    }

    protected function tearDown(): void
    {
        TemporaryDirectory::remove($this->directory);
    }

    public function testSecondCallIsServedFromTheCache(): void
    {
        $inner = new SpyWeatherProvider();
        $provider = $this->cached($inner);

        $first = $provider->current(new Coordinates(52.5075419, 13.4251364));
        $second = $provider->current(new Coordinates(52.5075419, 13.4251364));

        self::assertSame(1, $inner->calls);
        self::assertEquals($first, $second);
    }

    public function testNearbyPointsShareAnEntry(): void
    {
        $inner = new SpyWeatherProvider();
        $provider = $this->cached($inner);

        $provider->current(new Coordinates(52.5075, 13.4251));
        $provider->current(new Coordinates(52.5099, 13.4290));

        self::assertSame(1, $inner->calls);
    }

    public function testEntryExpiresAfterTheTtl(): void
    {
        $inner = new SpyWeatherProvider();
        $provider = $this->cached($inner);

        $provider->current(new Coordinates(52.5, 13.4));
        $this->clock->advance(self::TTL);
        $provider->current(new Coordinates(52.5, 13.4));

        self::assertSame(2, $inner->calls);
    }

    public function testFailuresAreNotCached(): void
    {
        $inner = new SpyWeatherProvider(failures: 1);
        $provider = $this->cached($inner);

        try {
            $provider->current(new Coordinates(52.5, 13.4));
            self::fail('WeatherUnavailable expected');
        } catch (WeatherUnavailable) {
        }
        $weather = $provider->current(new Coordinates(52.5, 13.4));

        self::assertSame(2, $inner->calls);
        self::assertSame('clear sky', $weather->description);
    }

    private function cached(WeatherProvider $inner): CachedWeatherProvider
    {
        return new CachedWeatherProvider($inner, new FilesystemCache($this->directory, $this->clock), self::TTL);
    }
}

final class SpyWeatherProvider implements WeatherProvider
{
    public int $calls = 0;

    public function __construct(
        private int $failures = 0,
    ) {
    }

    public function current(Coordinates $coordinates): WeatherData
    {
        ++$this->calls;
        if ($this->failures > 0) {
            --$this->failures;

            throw new WeatherUnavailable('down');
        }

        return new WeatherData(1.0, 3.5, 2.0, 'clear sky', '01d');
    }
}
