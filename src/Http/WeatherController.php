<?php

declare(strict_types=1);

namespace WeatherCities\Http;

use WeatherCities\Domain\CityWeather;
use WeatherCities\Domain\CityWeatherSorter;
use WeatherCities\Domain\Coordinates;
use WeatherCities\Domain\DistanceCalculator;
use WeatherCities\Domain\InvalidCoordinates;
use WeatherCities\Infrastructure\Cities\CityFileParser;
use WeatherCities\Weather\InvalidApiKey;
use WeatherCities\Weather\WeatherProvider;
use WeatherCities\Weather\WeatherUnavailable;

final readonly class WeatherController
{
    private const int UNPROCESSABLE = 422;
    private const int SERVICE_UNAVAILABLE = 503;

    /**
     * @param \Closure(string): void $log
     */
    public function __construct(
        private CityFileParser $parser,
        private string $citiesFile,
        private WeatherProvider $weather,
        private TemplateRenderer $renderer,
        private \Closure $log,
        private DistanceCalculator $distance = new DistanceCalculator(),
        private CityWeatherSorter $sorter = new CityWeatherSorter(),
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $lat = trim($request->query('lat') ?? '');
        $lon = trim($request->query('lon') ?? '');
        $form = ['lat' => $lat, 'lon' => $lon];

        $errors = [];
        $point = null;
        if ($lat !== '' || $lon !== '') {
            [$point, $errors] = $this->coordinates($lat, $lon);
        }
        if ($errors !== []) {
            return new Response(self::UNPROCESSABLE, $this->renderer->render('weather', [
                'form' => $form,
                'errors' => $errors,
                'rows' => [],
                'withDistance' => false,
            ]));
        }

        $rows = [];
        foreach ($this->parser->parse($this->citiesFile) as $city) {
            try {
                $weather = $this->weather->current($city->coordinates);
            } catch (InvalidApiKey $e) {
                // Every other city would fail the same way, so one clear message replaces the table.
                ($this->log)($e->getMessage());

                return new Response(self::SERVICE_UNAVAILABLE, $this->renderer->render('weather', [
                    'form' => $form,
                    'errors' => [$e->getMessage()],
                    'rows' => [],
                    'withDistance' => false,
                ]));
            } catch (WeatherUnavailable $e) {
                ($this->log)(sprintf('No weather for %s (%s): %s', $city->name, $city->zip, $e->getMessage()));
                $weather = null;
            }
            $distance = $point === null ? null : $this->distance->km($point, $city->coordinates);
            $rows[] = new CityWeather($city, $weather, $distance);
        }

        return new Response(200, $this->renderer->render('weather', [
            'form' => $form,
            'errors' => [],
            'rows' => $this->sorter->bySpread($rows),
            'withDistance' => $point !== null,
        ]));
    }

    /**
     * @return array{0: ?Coordinates, 1: list<string>}
     */
    private function coordinates(string $lat, string $lon): array
    {
        $errors = [];
        $latitude = filter_var($lat, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($lon, FILTER_VALIDATE_FLOAT);
        if ($latitude === false) {
            $errors[] = 'Latitude must be a number between -90 and 90.';
        }
        if ($longitude === false) {
            $errors[] = 'Longitude must be a number between -180 and 180.';
        }
        if ($latitude === false || $longitude === false) {
            return [null, $errors];
        }

        try {
            return [new Coordinates($latitude, $longitude), []];
        } catch (InvalidCoordinates $e) {
            return [null, [$e->getMessage()]];
        }
    }
}
