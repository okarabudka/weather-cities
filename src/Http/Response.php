<?php

declare(strict_types=1);

namespace WeatherCities\Http;

final readonly class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $status,
        public string $body,
        public array $headers = ['Content-Type' => 'text/html; charset=utf-8'],
    ) {
    }

    public static function text(int $status, string $body): self
    {
        return new self($status, $body, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
