<?php

declare(strict_types=1);

namespace WeatherCities\Http;

/** Matches method and exact path; no patterns, the application has one page. */
final class Router
{
    /** @var array<string, array<string, \Closure(Request): Response>> path => method => handler */
    private array $routes = [];

    /**
     * @param \Closure(Request): Response $handler
     */
    public function get(string $path, \Closure $handler): void
    {
        $this->routes[$path]['GET'] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $handlers = $this->routes[$request->path] ?? null;
        if ($handlers === null) {
            return Response::text(404, 'Not Found');
        }
        // HEAD is answered like GET; the web server drops the body.
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        $handler = $handlers[$method] ?? null;
        if ($handler === null) {
            return new Response(405, 'Method Not Allowed', [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Allow' => implode(', ', array_keys($handlers)),
            ]);
        }

        return $handler($request);
    }
}
