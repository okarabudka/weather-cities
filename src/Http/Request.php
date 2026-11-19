<?php

declare(strict_types=1);

namespace WeatherCities\Http;

final readonly class Request
{
    /**
     * @param array<string, string> $query
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : null;

        $query = [];
        foreach ($_GET as $name => $value) {
            // Arrays such as ?lat[]=1 are dropped; the application only reads scalar parameters.
            if (is_string($value)) {
                $query[(string) $name] = $value;
            }
        }

        return new self(
            is_string($method) ? strtoupper($method) : 'GET',
            is_string($path) && $path !== '' ? $path : '/',
            $query,
        );
    }

    public function query(string $name): ?string
    {
        return $this->query[$name] ?? null;
    }
}
