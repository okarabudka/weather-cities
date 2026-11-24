<?php

declare(strict_types=1);

namespace WeatherCities\Infrastructure\Cities;

use WeatherCities\Domain\City;
use WeatherCities\Domain\Coordinates;
use WeatherCities\Domain\InvalidCoordinates;

/**
 * Reads cities.dat: a header line, then `country zip city longitude latitude` separated by spaces;
 * names containing spaces are double-quoted. Note the order: longitude comes before latitude.
 */
final class CityFileParser
{
    private const int COLUMNS = 5;
    private const string HEADER = 'country zip city longitude latitude';

    /**
     * @return list<City>
     */
    public function parse(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidCitiesFile(sprintf('Cities file "%s" not found or not readable.', $path));
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new InvalidCitiesFile(sprintf('Cities file "%s" could not be read.', $path));
        }

        $cities = [];
        $headerSeen = false;
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!$headerSeen) {
                $this->checkHeader($line, $index + 1, $path);
                $headerSeen = true;
                continue;
            }
            $cities[] = $this->parseLine($line, $index + 1, $path);
        }

        return $cities;
    }

    private function checkHeader(string $line, int $lineNumber, string $path): void
    {
        $columns = preg_split('/\s+/', $line);
        if ($columns !== explode(' ', self::HEADER)) {
            throw $this->error($path, $lineNumber, sprintf('expected the header "%s"', self::HEADER));
        }
    }

    private function parseLine(string $line, int $lineNumber, string $path): City
    {
        $fields = array_values(array_filter(
            str_getcsv($line, ' ', '"', ''),
            static fn (?string $field): bool => $field !== null && $field !== '',
        ));
        if (count($fields) !== self::COLUMNS) {
            throw $this->error($path, $lineNumber, sprintf('expected %d columns, got %d', self::COLUMNS, count($fields)));
        }
        [$country, $zip, $name, $longitude, $latitude] = $fields;
        if (!is_numeric($longitude) || !is_numeric($latitude)) {
            throw $this->error($path, $lineNumber, 'longitude and latitude must be numbers');
        }

        try {
            $coordinates = new Coordinates((float) $latitude, (float) $longitude);
        } catch (InvalidCoordinates $e) {
            throw $this->error($path, $lineNumber, $e->getMessage());
        }

        return new City($country, $zip, $name, $coordinates);
    }

    private function error(string $path, int $lineNumber, string $reason): InvalidCitiesFile
    {
        return new InvalidCitiesFile(sprintf('%s, line %d: %s.', $path, $lineNumber, rtrim($reason, '.')));
    }
}
