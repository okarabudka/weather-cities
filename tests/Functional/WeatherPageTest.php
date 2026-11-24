<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Functional;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WeatherCities\Cache\FilesystemCache;
use WeatherCities\Http\Request;
use WeatherCities\Http\TemplateRenderer;
use WeatherCities\Http\WeatherController;
use WeatherCities\Infrastructure\Cities\CityFileParser;
use WeatherCities\Tests\Support\FakeHttpClient;
use WeatherCities\Tests\Support\FrozenClock;
use WeatherCities\Tests\Support\SampleWeather;
use WeatherCities\Tests\Support\TemporaryDirectory;
use WeatherCities\Weather\CachedWeatherProvider;
use WeatherCities\Weather\OpenWeatherMapProvider;

/**
 * The real object graph from public/index.php on the shipped data/cities.dat; only the HTTP client is fake,
 * so no network and no API key are needed.
 */
final class WeatherPageTest extends TestCase
{
    private const string ROOT = __DIR__ . '/../..';
    // Spread order of the sample fixture: Muenchen 0.4, Magdeburg 0.9, Bad Nauheim 1.2, Berlin 2.2, Giessen 2.7, Frankfurt 3.6.
    private const array EXPECTED_ORDER = ['Muenchen', 'Magdeburg', 'Bad Nauheim', 'Berlin', 'Giessen', 'Frankfurt am Main'];

    private string $cacheDir;
    /** @var list<string> */
    private array $log = [];

    protected function setUp(): void
    {
        $this->cacheDir = TemporaryDirectory::create();
    }

    protected function tearDown(): void
    {
        TemporaryDirectory::remove($this->cacheDir);
    }

    public function testListsAllCitiesBySpreadWithoutDistance(): void
    {
        $response = $this->controller(SampleWeather::client())(new Request('GET', '/'));

        self::assertSame(200, $response->status);
        self::assertSame(self::EXPECTED_ORDER, self::cityNames($response->body));
        self::assertSame(6, substr_count($response->body, '<td class="num">—</td>'));
    }

    public function testShowsDistancesForValidCoordinates(): void
    {
        $response = $this->controller(SampleWeather::client())(new Request('GET', '/', ['lat' => '52.5075419', 'lon' => '13.4251364']));

        self::assertSame(200, $response->status);
        self::assertSame(self::EXPECTED_ORDER, self::cityNames($response->body));
        self::assertStringNotContainsString('<td class="num">—</td>', $response->body);
        // Berlin to itself.
        self::assertStringContainsString('<td class="num">0.0</td>', $response->body);
    }

    public function testRepeatedRequestsAreServedFromTheCache(): void
    {
        $client = SampleWeather::client();
        $controller = $this->controller($client);

        $controller(new Request('GET', '/'));
        $controller(new Request('GET', '/', ['lat' => '50', 'lon' => '8']));

        self::assertCount(6, $client->requests);
        $files = glob($this->cacheDir . '/*.cache');
        self::assertIsArray($files);
        self::assertCount(6, $files);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidCoordinatesProvider(): iterable
    {
        yield 'latitude out of range' => ['91', '10', 'Latitude must be between -90 and 90'];
        yield 'longitude not a number' => ['50', 'abc', 'Longitude must be a number'];
        yield 'latitude missing' => ['', '10', 'Latitude must be a number'];
    }

    #[DataProvider('invalidCoordinatesProvider')]
    public function testInvalidCoordinatesAre422WithAMessage(string $lat, string $lon, string $message): void
    {
        $client = SampleWeather::client();

        $response = $this->controller($client)(new Request('GET', '/', ['lat' => $lat, 'lon' => $lon]));

        self::assertSame(422, $response->status);
        self::assertStringContainsString('class="errors"', $response->body);
        self::assertStringContainsString($message, $response->body);
        self::assertSame([], $client->requests);
    }

    public function testCityWithoutWeatherIsListedLastAndLogged(): void
    {
        $client = SampleWeather::client(['52.51,13.43']);

        $response = $this->controller($client)(new Request('GET', '/'));

        self::assertSame(200, $response->status);
        self::assertSame(['Muenchen', 'Magdeburg', 'Bad Nauheim', 'Giessen', 'Frankfurt am Main', 'Berlin'], self::cityNames($response->body));
        self::assertStringContainsString('<tr class="missing">', $response->body);
        self::assertStringContainsString('no data', $response->body);
        self::assertSame(['No weather for Berlin (10115): OpenWeatherMap answered with HTTP 500.'], $this->log);
    }

    public function testInvalidApiKeyShowsOneMessageInsteadOfTheTable(): void
    {
        $client = new FakeHttpClient(static fn (): Response => new Response(401, [], '{"cod":401,"message":"Invalid API key"}'));

        $response = $this->controller($client)(new Request('GET', '/'));

        self::assertSame(503, $response->status);
        self::assertStringContainsString('class="errors"', $response->body);
        self::assertStringContainsString('OpenWeatherMap rejected the API key (HTTP 401). Check OWM_API_KEY in .env.', $response->body);
        self::assertStringNotContainsString('no data', $response->body);
        self::assertStringNotContainsString('<table>', $response->body);
        self::assertCount(1, $client->requests);
        self::assertSame(['OpenWeatherMap rejected the API key (HTTP 401). Check OWM_API_KEY in .env.'], $this->log);
    }

    private function controller(FakeHttpClient $client): WeatherController
    {
        return new WeatherController(
            new CityFileParser(),
            self::ROOT . '/data/cities.dat',
            new CachedWeatherProvider(
                new OpenWeatherMapProvider($client, new HttpFactory(), 'test-key'),
                new FilesystemCache($this->cacheDir, new FrozenClock()),
                600,
            ),
            new TemplateRenderer(self::ROOT . '/templates'),
            function (string $message): void {
                $this->log[] = $message;
            },
        );
    }

    /**
     * @return list<string>
     */
    private static function cityNames(string $html): array
    {
        preg_match_all('~<td>(?:\d+)</td>\s*<td>([^<]+?) <span class="zip">~', $html, $matches);

        return $matches[1];
    }
}
