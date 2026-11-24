<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use WeatherCities\Domain\City;
use WeatherCities\Infrastructure\Cities\CityFileParser;
use WeatherCities\Infrastructure\Cities\InvalidCitiesFile;
use WeatherCities\Tests\Support\TemporaryDirectory;

final class CityFileParserTest extends TestCase
{
    private const string CITIES_FILE = __DIR__ . '/../../../data/cities.dat';

    private string $directory;

    protected function setUp(): void
    {
        $this->directory = TemporaryDirectory::create();
    }

    protected function tearDown(): void
    {
        TemporaryDirectory::remove($this->directory);
    }

    public function testParsesTheShippedFile(): void
    {
        $cities = (new CityFileParser())->parse(self::CITIES_FILE);

        self::assertCount(6, $cities);
        self::assertSame(
            ['Giessen', 'Magdeburg', 'Berlin', 'Frankfurt am Main', 'Muenchen', 'Bad Nauheim'],
            array_map(static fn (City $city): string => $city->name, $cities),
        );
    }

    public function testReadsLongitudeBeforeLatitude(): void
    {
        $giessen = (new CityFileParser())->parse(self::CITIES_FILE)[0];

        self::assertSame('DE', $giessen->country);
        self::assertSame('35390', $giessen->zip);
        self::assertEqualsWithDelta(50.5859, $giessen->coordinates->latitude, 1e-4);
        self::assertEqualsWithDelta(8.6730, $giessen->coordinates->longitude, 1e-4);
    }

    public function testSkipsBlankLinesAndComments(): void
    {
        $file = $this->write("# comment\n\ncountry zip city longitude latitude\n\nDE 01067 Dresden 13.7373 51.0504\n# end\n");

        $cities = (new CityFileParser())->parse($file);

        self::assertCount(1, $cities);
        self::assertSame('01067', $cities[0]->zip);
    }

    public function testMissingHeaderIsRejected(): void
    {
        $file = $this->write("# no header\nDE 10115 Berlin 13.4 52.5\nDE 39104 Magdeburg 11.6 52.1\n");

        $this->expectException(InvalidCitiesFile::class);
        $this->expectExceptionMessage('line 2: expected the header "country zip city longitude latitude".');

        (new CityFileParser())->parse($file);
    }

    public function testHeaderWithSwappedCoordinatesIsRejected(): void
    {
        $file = $this->write("country zip city latitude longitude\nDE 10115 Berlin 52.5 13.4\n");

        $this->expectException(InvalidCitiesFile::class);
        $this->expectExceptionMessage('line 1: expected the header "country zip city longitude latitude".');

        (new CityFileParser())->parse($file);
    }

    public function testBrokenLineReportsItsNumber(): void
    {
        $file = $this->write("country zip city longitude latitude\nDE 10115 Berlin 13.4 52.5\nDE 39104 Magdeburg 11.6\n");

        $this->expectException(InvalidCitiesFile::class);
        $this->expectExceptionMessage('line 3: expected 5 columns, got 4.');

        (new CityFileParser())->parse($file);
    }

    public function testNonNumericCoordinatesAreRejected(): void
    {
        $file = $this->write("country zip city longitude latitude\nDE 10115 Berlin east 52.5\n");

        $this->expectException(InvalidCitiesFile::class);
        $this->expectExceptionMessage('line 2: longitude and latitude must be numbers.');

        (new CityFileParser())->parse($file);
    }

    public function testOutOfRangeCoordinatesAreRejected(): void
    {
        $file = $this->write("country zip city longitude latitude\nDE 10115 Berlin 13.4 95.0\n");

        $this->expectException(InvalidCitiesFile::class);
        $this->expectExceptionMessage('line 2: Latitude must be between -90 and 90');

        (new CityFileParser())->parse($file);
    }

    public function testMissingFileIsAnError(): void
    {
        $this->expectException(InvalidCitiesFile::class);

        (new CityFileParser())->parse($this->directory . '/missing.dat');
    }

    private function write(string $content): string
    {
        $file = $this->directory . '/cities.dat';
        file_put_contents($file, $content);

        return $file;
    }
}
