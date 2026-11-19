<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Weather;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use WeatherCities\Domain\Coordinates;
use WeatherCities\Tests\Support\FakeHttpClient;
use WeatherCities\Weather\OpenWeatherMapProvider;
use WeatherCities\Weather\WeatherUnavailable;

final class OpenWeatherMapProviderTest extends TestCase
{
    private const string API_KEY = 'not-a-real-key';
    private const string FIXTURE = __DIR__ . '/../../Fixtures/owm-current.json';

    public function testMapsASuccessfulResponse(): void
    {
        $client = new FakeHttpClient(static fn (): Response => new Response(200, [], self::fixture()));

        $weather = $this->provider($client)->current(new Coordinates(52.5075, 13.4251));

        self::assertSame(5.1, $weather->tempMin);
        self::assertSame(7.3, $weather->tempMax);
        self::assertSame(6.4, $weather->tempCurrent);
        self::assertSame('broken clouds', $weather->description);
        self::assertSame('04d', $weather->icon);
    }

    public function testRequestsMetricUnitsForTheGivenPoint(): void
    {
        $client = new FakeHttpClient(static fn (): Response => new Response(200, [], self::fixture()));

        $this->provider($client)->current(new Coordinates(52.5075, 13.4251));

        self::assertCount(1, $client->requests);
        $uri = $client->requests[0]->getUri();
        self::assertSame('GET', $client->requests[0]->getMethod());
        self::assertSame('api.openweathermap.org', $uri->getHost());
        self::assertSame('/data/2.5/weather', $uri->getPath());
        parse_str($uri->getQuery(), $query);
        self::assertSame(['lat' => '52.5075', 'lon' => '13.4251', 'units' => 'metric', 'appid' => self::API_KEY], $query);
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function failureProvider(): iterable
    {
        yield 'invalid key' => [401, '{"cod":401,"message":"Invalid API key"}'];
        yield 'rate limited' => [429, '{"cod":429}'];
        yield 'server error' => [500, 'oops'];
        yield 'broken JSON' => [200, '{"main": '];
        yield 'JSON without main' => [200, '{"weather": [{"description": "x", "icon": "01d"}]}'];
        yield 'temperature not a number' => [200, '{"main": {"temp": "6", "temp_min": 5, "temp_max": 7}, "weather": [{"description": "x", "icon": "01d"}]}'];
        yield 'empty weather list' => [200, '{"main": {"temp": 6, "temp_min": 5, "temp_max": 7}, "weather": []}'];
    }

    #[DataProvider('failureProvider')]
    public function testFailuresBecomeWeatherUnavailableWithoutTheKey(int $status, string $body): void
    {
        $client = new FakeHttpClient(static fn (): Response => new Response($status, [], $body));

        try {
            $this->provider($client)->current(new Coordinates(52.5, 13.4));
            self::fail('WeatherUnavailable expected');
        } catch (WeatherUnavailable $e) {
            self::assertStringNotContainsString(self::API_KEY, $e->getMessage());
            self::assertSame($status, $e->getCode());
        }
    }

    public function testErrorStatusIsInTheMessage(): void
    {
        $client = new FakeHttpClient(static fn (): Response => new Response(401, [], '{}'));

        $this->expectException(WeatherUnavailable::class);
        $this->expectExceptionMessage('HTTP 401');

        $this->provider($client)->current(new Coordinates(52.5, 13.4));
    }

    public function testTransportErrorDoesNotLeakTheUrl(): void
    {
        $client = new FakeHttpClient(static function (RequestInterface $request): never {
            throw new class('Could not resolve ' . $request->getUri(), $request) extends \RuntimeException implements NetworkExceptionInterface {
                public function __construct(string $message, private readonly RequestInterface $request)
                {
                    parent::__construct($message);
                }

                public function getRequest(): RequestInterface
                {
                    return $this->request;
                }
            };
        });

        try {
            $this->provider($client)->current(new Coordinates(52.5, 13.4));
            self::fail('WeatherUnavailable expected');
        } catch (WeatherUnavailable $e) {
            self::assertStringNotContainsString(self::API_KEY, $e->getMessage());
            self::assertNull($e->getPrevious());
        }
    }

    private function provider(FakeHttpClient $client): OpenWeatherMapProvider
    {
        return new OpenWeatherMapProvider($client, new HttpFactory(), self::API_KEY);
    }

    private static function fixture(): string
    {
        $json = file_get_contents(self::FIXTURE);
        self::assertIsString($json);

        return $json;
    }
}
