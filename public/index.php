<?php

declare(strict_types=1);

// Composition root: reads the configuration and wires the object graph by hand.

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use WeatherCities\Cache\FilesystemCache;
use WeatherCities\Clock\SystemClock;
use WeatherCities\Config;
use WeatherCities\Http\Request;
use WeatherCities\Http\Response;
use WeatherCities\Http\Router;
use WeatherCities\Http\TemplateRenderer;
use WeatherCities\Http\WeatherController;
use WeatherCities\Infrastructure\Cities\CityFileParser;
use WeatherCities\Weather\CachedWeatherProvider;
use WeatherCities\Weather\OpenWeatherMapProvider;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

ini_set('display_errors', '0');
set_exception_handler(static function (\Throwable $e): void {
    error_log(sprintf('Unhandled %s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
    Response::text(500, 'Internal Server Error')->send();
});

$config = Config::fromEnvironment(getenv(), $root);
if ($config->owmApiKey === '') {
    Response::text(503, "OWM_API_KEY is not set.\nCopy .env.example to .env, add your OpenWeatherMap key and run `make up` again (see README).")->send();

    return;
}

$weather = new CachedWeatherProvider(
    new OpenWeatherMapProvider(new Client(['timeout' => 5.0]), new HttpFactory(), $config->owmApiKey),
    new FilesystemCache($config->cacheDir, new SystemClock()),
    $config->cacheTtl,
);
$controller = new WeatherController(
    new CityFileParser(),
    $config->citiesFile,
    $weather,
    new TemplateRenderer($root . '/templates'),
    static function (string $message): void {
        error_log($message);
    },
);

$router = new Router();
$router->get('/', $controller(...));
$router->dispatch(Request::fromGlobals())->send();
