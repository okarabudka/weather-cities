<?php

declare(strict_types=1);

namespace WeatherCities\Weather;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use WeatherCities\Domain\Coordinates;
use WeatherCities\Domain\WeatherData;

/**
 * Adapter for the OpenWeatherMap current weather endpoint. The API key travels in the query string, so neither the
 * URL nor the key ever goes into exception messages or logs.
 */
final readonly class OpenWeatherMapProvider implements WeatherProvider
{
    private const string ENDPOINT = 'https://api.openweathermap.org/data/2.5/weather';
    private const int HTTP_OK = 200;

    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        #[\SensitiveParameter]
        private string $apiKey,
    ) {
    }

    public function current(Coordinates $coordinates): WeatherData
    {
        $query = http_build_query([
            'lat' => $coordinates->latitude,
            'lon' => $coordinates->longitude,
            'units' => 'metric',
            'appid' => $this->apiKey,
        ]);
        $request = $this->requestFactory->createRequest('GET', self::ENDPOINT . '?' . $query);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new WeatherUnavailable(sprintf('OpenWeatherMap request failed (%s).', $e::class));
        }

        $status = $response->getStatusCode();
        if ($status !== self::HTTP_OK) {
            throw new WeatherUnavailable(sprintf('OpenWeatherMap answered with HTTP %d.', $status), $status);
        }

        try {
            $data = json_decode((string) $response->getBody(), true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new WeatherUnavailable('OpenWeatherMap returned invalid JSON.', $status);
        }

        return $this->map($data, $status);
    }

    private function map(mixed $data, int $status): WeatherData
    {
        if (!is_array($data) || !is_array($data['main'] ?? null) || !is_array($data['weather'] ?? null)) {
            throw new WeatherUnavailable('OpenWeatherMap response lacks "main" or "weather".', $status);
        }
        $main = $data['main'];
        $condition = $data['weather'][0] ?? null;
        $tempMin = $main['temp_min'] ?? null;
        $tempMax = $main['temp_max'] ?? null;
        $temp = $main['temp'] ?? null;
        if (!is_int($tempMin) && !is_float($tempMin)
            || !is_int($tempMax) && !is_float($tempMax)
            || !is_int($temp) && !is_float($temp)
            || !is_array($condition)
            || !is_string($condition['description'] ?? null)
            || !is_string($condition['icon'] ?? null)
        ) {
            throw new WeatherUnavailable('OpenWeatherMap response has missing or malformed fields.', $status);
        }

        return new WeatherData(
            (float) $tempMin,
            (float) $tempMax,
            (float) $temp,
            $condition['description'],
            $condition['icon'],
        );
    }
}
