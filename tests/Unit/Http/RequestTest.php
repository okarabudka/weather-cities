<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;
use WeatherCities\Http\Request;

#[BackupGlobals(true)]
final class RequestTest extends TestCase
{
    public function testReadsMethodPathAndScalarQueryParameters(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $_SERVER['REQUEST_URI'] = '/?lat=52.5&lon[]=1';
        $_GET = ['lat' => '52.5', 'lon' => ['1']];

        $request = Request::fromGlobals();

        self::assertSame('GET', $request->method);
        self::assertSame('/', $request->path);
        self::assertSame('52.5', $request->query('lat'));
        self::assertNull($request->query('lon'));
    }
}
