<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Fake OpenWeatherMap: answers from tests/Fixtures/owm-sample.json (sample values, not real measurements), keyed by
 * latitude and longitude rounded to two decimals. Unknown points get HTTP 404 like OWM does.
 */
final class SampleWeather
{
    private const string FIXTURE = __DIR__ . '/../Fixtures/owm-sample.json';

    /**
     * @param list<string> $failingPoints "lat,lon" keys that answer with HTTP 500
     */
    public static function client(array $failingPoints = []): FakeHttpClient
    {
        $json = file_get_contents(self::FIXTURE);
        $responses = is_string($json) ? json_decode($json, true, 16, JSON_THROW_ON_ERROR) : null;
        if (!is_array($responses)) {
            throw new \RuntimeException('Fixture owm-sample.json could not be read.');
        }

        return new FakeHttpClient(static function (RequestInterface $request) use ($responses, $failingPoints): Response {
            parse_str($request->getUri()->getQuery(), $query);
            $key = sprintf('%.2f,%.2f', self::number($query['lat'] ?? null), self::number($query['lon'] ?? null));
            if (in_array($key, $failingPoints, true)) {
                return new Response(500, [], '{"cod":500}');
            }
            if (!isset($responses[$key])) {
                return new Response(404, [], '{"cod":"404","message":"city not found"}');
            }

            return new Response(200, ['Content-Type' => 'application/json'], json_encode($responses[$key], JSON_THROW_ON_ERROR));
        });
    }

    private static function number(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : NAN;
    }
}
