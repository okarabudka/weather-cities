<?php

declare(strict_types=1);

// Dev only: renders the page through the real controller and templates with sample weather from
// tests/Fixtures/owm-sample.json instead of OpenWeatherMap. Used for the README screenshot.
// Usage: php bin/render-sample.php [lat] [lon] > page.html

use GuzzleHttp\Psr7\HttpFactory;
use WeatherCities\Cache\FilesystemCache;
use WeatherCities\Clock\SystemClock;
use WeatherCities\Http\Request;
use WeatherCities\Http\TemplateRenderer;
use WeatherCities\Http\WeatherController;
use WeatherCities\Infrastructure\Cities\CityFileParser;
use WeatherCities\Tests\Support\SampleWeather;
use WeatherCities\Tests\Support\TemporaryDirectory;
use WeatherCities\Weather\CachedWeatherProvider;
use WeatherCities\Weather\OpenWeatherMapProvider;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$query = [];
if (isset($argv[1], $argv[2])) {
    $query = ['lat' => $argv[1], 'lon' => $argv[2]];
}

$cacheDir = TemporaryDirectory::create();
try {
    $controller = new WeatherController(
        new CityFileParser(),
        $root . '/data/cities.dat',
        new CachedWeatherProvider(
            new OpenWeatherMapProvider(SampleWeather::client(), new HttpFactory(), 'sample'),
            new FilesystemCache($cacheDir, new SystemClock()),
            60,
        ),
        new TemplateRenderer($root . '/templates'),
        static function (string $message): void {
            fwrite(STDERR, $message . PHP_EOL);
        },
    );
    echo $controller(new Request('GET', '/', $query))->body;
} finally {
    TemporaryDirectory::remove($cacheDir);
}
