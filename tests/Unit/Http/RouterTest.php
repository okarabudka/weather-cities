<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use WeatherCities\Http\Request;
use WeatherCities\Http\Response;
use WeatherCities\Http\Router;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/', static fn (Request $request): Response => new Response(200, 'home'));
    }

    public function testGetMatchesTheExactPath(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/'));

        self::assertSame(200, $response->status);
        self::assertSame('home', $response->body);
    }

    public function testHeadIsAnsweredLikeGet(): void
    {
        self::assertSame(200, $this->router->dispatch(new Request('HEAD', '/'))->status);
    }

    public function testOtherMethodIs405WithAllowHeader(): void
    {
        $response = $this->router->dispatch(new Request('POST', '/'));

        self::assertSame(405, $response->status);
        self::assertSame('GET', $response->headers['Allow']);
    }

    public function testUnknownPathIs404(): void
    {
        self::assertSame(404, $this->router->dispatch(new Request('GET', '/x'))->status);
        self::assertSame(404, $this->router->dispatch(new Request('GET', '/index.php'))->status);
    }
}
