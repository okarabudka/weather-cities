# Weather Cities

A weather-by-city web application (OpenWeatherMap), currently at the stage of a project skeleton plus a detailed
implementation plan.

## Context

Started in April 2026 as a take-home style task: read a list of cities with coordinates, fetch the current weather for
each one and show them in a table next to a coordinate input form. The task requires plain PHP without a framework,
with an easily swappable weather provider. The repository shows how the task was approached before coding: the full
design is written down in [`plan/PLAN.md`](plan/PLAN.md) and a sample input file is in
[`plan/cities.dat`](plan/cities.dat). The repository currently contains a generic Dockerised Symfony skeleton as
scaffolding; the application code itself has not been implemented yet.

## Features

Implemented:

- PHP 8.4-FPM + Nginx environment in Docker Compose
- GitHub Actions CI workflow (composer validation, PHP lint, PHPStan, PHPUnit)

Planned (see `plan/PLAN.md`):

- Parse the cities file (country, postal code, city name, coordinates)
- Fetch current weather for every city from the OpenWeatherMap API
- Web page with a latitude/longitude input form
- Table of cities sorted by temperature spread (max minus min), ascending, with min/max temperature, distance to the
  entered coordinates in km, and weather description with icon
- Metric units

## Tech stack

Current scaffolding: PHP 8.4, Symfony 8.0 skeleton, PHPUnit 13, PHPStan 2, Docker Compose, Nginx, GitHub Actions.

Planned implementation: plain PHP 8.1+ without a framework, Composer libraries only (Guzzle HTTP client, optional
Redis client).

## Architecture

Planned design (from `plan/PLAN.md`):

- Front controller `public/index.php` with a simple router and controller
- `WeatherProviderInterface` with an OpenWeatherMap implementation, so another provider can be plugged in by changing
  one line
- `CachedWeatherProvider` as a decorator that adds caching to any provider; `CacheInterface` with filesystem and Redis
  implementations
- `HttpClientInterface` so the HTTP library can be replaced or mocked in tests
- Readonly DTOs `City` and `WeatherData`; Haversine formula for distances
- Plain PHP templates with output escaping; server-side validation of `lat`/`lon`

Planned configuration is environment-driven, for example:

```dotenv
OWM_API_KEY=your_openweathermap_api_key
CACHE_DRIVER=filesystem   # filesystem | redis | none
CACHE_TTL=600
CITIES_FILE=cities.dat
```

## Getting started

Requirements: Docker with Docker Compose.

```bash
git clone https://github.com/okarabudka/weather-cities.git
cd weather-cities

docker compose up -d --build
docker compose exec app composer install
```

Nginx listens on http://localhost:8080 (override with `APP_PORT`). At this stage it serves the skeleton only.

## Testing

There are no tests yet (`tests/` contains only the bootstrap file). The CI workflow in `.github/workflows/ci.yml` runs
the checks that can be reproduced locally:

```bash
docker compose exec app composer validate
docker compose exec app php bin/phpunit
docker compose exec app vendor/bin/phpstan analyse
```

PHPStan has no configuration file in the repository yet, so it needs paths passed explicitly until one is added.
The plan describes unit tests for the distance calculation, the cities parser and the caching decorator.

## Status

Planning stage since April 2026: environment and implementation plan are in place, application code is not
implemented.

## License

MIT, see [LICENSE](LICENSE).
