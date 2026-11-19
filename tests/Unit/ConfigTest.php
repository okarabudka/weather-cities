<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WeatherCities\Config;

final class ConfigTest extends TestCase
{
    public function testDefaultsAreRelativeToTheProjectRoot(): void
    {
        $config = Config::fromEnvironment([], '/app');

        self::assertSame('', $config->owmApiKey);
        self::assertSame(600, $config->cacheTtl);
        self::assertSame('/app/var/cache', $config->cacheDir);
        self::assertSame('/app/data/cities.dat', $config->citiesFile);
    }

    public function testReadsTheEnvironment(): void
    {
        $config = Config::fromEnvironment([
            'OWM_API_KEY' => ' abc ',
            'CACHE_TTL' => '60',
            'CACHE_DIR' => '/tmp/weather',
            'CITIES_FILE' => 'other/cities.dat',
        ], '/app');

        self::assertSame('abc', $config->owmApiKey);
        self::assertSame(60, $config->cacheTtl);
        self::assertSame('/tmp/weather', $config->cacheDir);
        self::assertSame('/app/other/cities.dat', $config->citiesFile);
    }

    public function testRejectsAnInvalidTtl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Config::fromEnvironment(['CACHE_TTL' => 'ten'], '/app');
    }
}
