<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use WeatherCities\Domain\City;
use WeatherCities\Domain\CityWeather;
use WeatherCities\Domain\Coordinates;
use WeatherCities\Domain\WeatherData;
use WeatherCities\Http\TemplateRenderer;

final class TemplateRendererTest extends TestCase
{
    private const string TEMPLATES = __DIR__ . '/../../../templates';

    public function testEscapesCityNamesAndWeatherText(): void
    {
        $city = new City('DE', '"><b>', '<script>alert(1)</script>', new Coordinates(50.0, 8.0));
        $weather = new WeatherData(1.0, 2.0, 1.5, '<img src=x onerror=alert(2)>', '01d');

        $html = (new TemplateRenderer(self::TEMPLATES))->render('weather', [
            'form' => ['lat' => '"><script>', 'lon' => ''],
            'errors' => ['<i>bad</i>'],
            'rows' => [new CityWeather($city, $weather, 12.0)],
            'withDistance' => true,
        ]);

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('<img src=x', $html);
        self::assertStringNotContainsString('<i>bad</i>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringContainsString('value="&quot;&gt;&lt;script&gt;"', $html);
        self::assertStringContainsString('<title>Weather by Cities</title>', $html);
    }

    public function testEscapeHelper(): void
    {
        self::assertSame('&lt;a href=&#039;x&#039;&gt;&amp;', TemplateRenderer::e("<a href='x'>&"));
    }

    public function testUnknownTemplateIsAnError(): void
    {
        $level = ob_get_level();

        try {
            (new TemplateRenderer(self::TEMPLATES))->render('missing');
            self::fail('RuntimeException expected');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('missing', $e->getMessage());
        }
        self::assertSame($level, ob_get_level());
    }
}
