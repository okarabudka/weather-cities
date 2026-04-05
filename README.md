# Weather Cities

> Pure PHP (no framework). OOP, SOLID, swappable weather provider, caching via the Decorator pattern.

---

## What the app does

1. Reads a `cities.dat` file in GeoNames postal-code format (tab-delimited, 12 columns).
2. Fetches current weather for every city from the OpenWeatherMap API.
3. Shows a web page with a coordinate input form (lat / lon).
4. Displays a table of cities sorted by **temperature spread** (max − min), ascending.
5. Table columns: city name, min °C, max °C, distance to the entered coordinates (km), weather description + icon.
6. Unit system: metric (°C, km).

---

## Requirements

- PHP >= 8.1
- Composer
- Docker + Docker Compose *(optional, for containerised setup)*
- OpenWeatherMap API key (free tier is sufficient)

---

## Project structure

```
weather-cities/
├── cities.dat                    # City data file (GeoNames format)
├── composer.json
├── composer.lock
├── .env                          # API key and settings (not committed)
├── .env.example
├── docker-compose.yml
├── Dockerfile
├── docker/
│   └── nginx.conf
├── public/
│   └── index.php                 # Front controller (single entry point)
├── src/
│   ├── Cache/
│   │   ├── CacheInterface.php
│   │   ├── FilesystemCache.php
│   │   └── RedisCache.php
│   ├── Config/
│   │   └── Config.php
│   ├── DTO/
│   │   ├── City.php
│   │   └── WeatherData.php
│   ├── Geo/
│   │   └── GeoCalculator.php
│   ├── Http/
│   │   ├── HttpClientInterface.php
│   │   ├── GuzzleHttpClient.php
│   │   ├── Request.php
│   │   └── Response.php
│   ├── Parser/
│   │   └── CityParser.php
│   ├── Router/
│   │   └── Router.php
│   ├── Template/
│   │   └── TemplateEngine.php
│   ├── Weather/
│   │   ├── WeatherProviderInterface.php
│   │   ├── OpenWeatherMapProvider.php
│   │   └── CachedWeatherProvider.php
│   └── Controller/
│       └── WeatherController.php
├── templates/
│   ├── layout.php
│   └── weather.php
├── var/
│   └── cache/                    # Filesystem cache directory
└── tests/
    ├── GeoCalculatorTest.php
    ├── CityParserTest.php
    └── CachedWeatherProviderTest.php
```

---

## Architecture overview

```
public/index.php
    └── Router
          └── WeatherController
                ├── CityParser          → City[]
                ├── CachedWeatherProvider (Decorator)
                │     ├── CacheInterface (FilesystemCache | RedisCache)
                │     └── WeatherProviderInterface
                │           └── OpenWeatherMapProvider
                │                 └── HttpClientInterface
                │                       └── GuzzleHttpClient
                ├── GeoCalculator       → float (km)
                └── TemplateEngine      → HTML
```

### Key design decisions

| Component | Pattern / Note |
|---|---|
| `WeatherProviderInterface` | Single method `getWeather(lat, lon): WeatherData`. Any provider (OWM, AccuWeather, WeatherAPI…) implements it. Swap by changing one line in the front controller. |
| `CachedWeatherProvider` | **Decorator** — wraps any `WeatherProviderInterface`, adds transparent caching. Cache key = MD5 of coordinates rounded to 2 decimal places (~1.1 km precision). TTL is configurable. |
| `CacheInterface` | `get / set / has`. Implementations: `FilesystemCache` (serialised files) and `RedisCache` (`predis/predis` or `ext-redis`). |
| `City` / `WeatherData` | **Readonly DTOs** — immutable, no logic except `WeatherData::spread()` and `WeatherData::iconUrl()`. |
| `GeoCalculator` | Haversine formula for great-circle distance in kilometres. |
| `Router` | Simple front-controller, exact path matching, no regex. |
| `TemplateEngine` | `extract()` + `include`. No Twig/Blade. |
| `HttpClientInterface` | Lets you swap Guzzle for native curl or a test mock without touching provider code. |

---

## Quick start

### Without Docker (PHP built-in server)

```bash
composer install
cp .env.example .env
# Fill in OWM_API_KEY and other settings in .env
php -S localhost:8000 -t public/
```

Open http://localhost:8000 in your browser.

### With Docker

```bash
cp .env.example .env
# Fill in OWM_API_KEY in .env
docker-compose up --build -d

# Verify
curl http://localhost:8080/
```

---

## Configuration

Copy `.env.example` to `.env` and adjust:

```dotenv
OWM_API_KEY=your_openweathermap_api_key_here
CACHE_DRIVER=filesystem         # filesystem | redis | none
CACHE_TTL=600                   # seconds
CACHE_DIR=var/cache
REDIS_HOST=redis
REDIS_PORT=6379
CITIES_FILE=cities.dat
```

Add `.env` to `.gitignore` — it must not be committed.

### Getting an OpenWeatherMap API key

1. Register at https://openweathermap.org/
2. Go to **API Keys**: https://home.openweathermap.org/api_keys
3. Create a new key. It activates within ~10 minutes.

**Verify the key:**
```bash
curl "https://api.openweathermap.org/data/2.5/weather?lat=52.52&lon=13.41&units=metric&appid=YOUR_KEY"
```

---

## `cities.dat` format

The file uses the standard **GeoNames postal code format** — tab-delimited (`\t`), 12 columns per line:

| # | Field | Description | Example |
|---|---|---|---|
| 0 | country_code | ISO-3166 two-letter country code | `DE` |
| 1 | postal_code | Postal code (string, may have leading zeros) | `10115` |
| 2 | place_name | City / place name | `Berlin` |
| 3 | admin_name1 | Region (level 1) | `Berlin` |
| 4 | admin_code1 | Level-1 region code | `16` |
| 5 | admin_name2 | District (level 2) | `Berlin` |
| 6 | admin_code2 | Level-2 district code | `00` |
| 7 | admin_name3 | Sub-district (level 3) | *(empty)* |
| 8 | admin_code3 | Level-3 code | *(empty)* |
| 9 | latitude | Decimal latitude | `52.5200` |
| 10 | longitude | Decimal longitude | `13.4050` |
| 11 | accuracy | 1=estimated, 4=geonameid, 6=centroid | `4` |

Lines starting with `#` are comments and are skipped. Lines with fewer than 11 fields are skipped.

**Download data:**
- Germany: https://download.geonames.org/export/zip/DE.zip
- All countries: https://download.geonames.org/export/zip/allCountries.zip

After downloading, rename or symlink the extracted `.txt` file to `cities.dat`.

---

## API rate limits and caching

The OpenWeatherMap free tier allows **60 requests/minute**. Without caching, a file with more than 60 cities will hit the rate limit on the first cold load.

**Recommended:** set `CACHE_DRIVER=filesystem`. On subsequent page loads, all weather data is served from cache instantly (TTL default: 600 s).

For large datasets (1000+ cities) and no cached data, consider enabling parallel requests via `GuzzleHttp\Pool` (see `plan/PLAN.md` — Phase 10).

---

## Running tests

```bash
./vendor/bin/phpunit tests/
```

Tests cover:
- `GeoCalculatorTest` — Haversine formula correctness (same-point, Berlin → Hamburg)
- `CityParserTest` — valid file parsing, missing file exception, invalid coordinate skipping
- `CachedWeatherProviderTest` — single API call for repeated coordinates, coordinate rounding increases cache hits

---

## Security

- All user input (`lat`, `lon`) is validated server-side with `FILTER_VALIDATE_FLOAT` and range checks.
- HTML output is escaped via `TemplateEngine::e()` (`htmlspecialchars`).
- `FilesystemCache` hashes keys with `sha1()` before building file paths — prevents path traversal.
- nginx blocks direct access to `.env` and `.git` via `location ~ /\.(env|git) { deny all; }`.

---

## Extending the app

### Swap the weather provider

1. Create `src/Weather/WeatherApiProvider.php` implementing `WeatherProviderInterface`.
2. In `public/index.php`, replace one line:
   ```php
   // Before:
   $weatherProvider = new OpenWeatherMapProvider($httpClient, $config->require('OWM_API_KEY'));
   // After:
   $weatherProvider = new WeatherApiProvider($httpClient, $config->require('WEATHERAPI_KEY'));
   ```
3. `CachedWeatherProvider` works unchanged.

### Swap the HTTP client

1. Create `src/Http/CurlHttpClient.php` implementing `HttpClientInterface`.
2. In `public/index.php`, replace one line:
   ```php
   $httpClient = new CurlHttpClient(timeout: 10);
   ```
3. `OpenWeatherMapProvider` works unchanged.

### Add a new cache backend (e.g. Memcached)

Implement `CacheInterface` (`get / set / has`) and wire it up in `public/index.php` inside the `match` expression for `CACHE_DRIVER`.

### Use `ext-redis` instead of `predis/predis`

Replace `RedisCache` internals with the native `\Redis` class (`$this->redis->connect($host, $port)`) — the `CacheInterface` contract stays the same.
