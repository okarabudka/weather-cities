<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/** PSR-18 client that answers from a callback and records every request; no network. */
final class FakeHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /**
     * @param \Closure(RequestInterface): ResponseInterface $respond
     */
    public function __construct(
        private readonly \Closure $respond,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return ($this->respond)($request);
    }
}
