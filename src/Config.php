<?php

declare(strict_types=1);

namespace WeatherCities;

/** Settings from environment variables; see .env.example. */
final readonly class Config
{
    private const int DEFAULT_CACHE_TTL = 600;
    private const string DEFAULT_CACHE_DIR = 'var/cache';
    private const string DEFAULT_CITIES_FILE = 'data/cities.dat';

    public function __construct(
        #[\SensitiveParameter]
        public string $owmApiKey,
        public int $cacheTtl,
        public string $cacheDir,
        public string $citiesFile,
    ) {
    }

    /**
     * @param array<string, string> $env
     */
    public static function fromEnvironment(array $env, string $projectRoot): self
    {
        $ttl = trim($env['CACHE_TTL'] ?? '');
        $cacheTtl = $ttl === '' ? self::DEFAULT_CACHE_TTL : filter_var($ttl, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($cacheTtl === false) {
            throw new \InvalidArgumentException(sprintf('CACHE_TTL must be a non-negative integer, got "%s".', $ttl));
        }

        return new self(
            trim($env['OWM_API_KEY'] ?? ''),
            $cacheTtl,
            self::path($env['CACHE_DIR'] ?? '', self::DEFAULT_CACHE_DIR, $projectRoot),
            self::path($env['CITIES_FILE'] ?? '', self::DEFAULT_CITIES_FILE, $projectRoot),
        );
    }

    private static function path(string $value, string $default, string $projectRoot): string
    {
        $value = trim($value) === '' ? $default : trim($value);

        return str_starts_with($value, '/') ? $value : $projectRoot . '/' . $value;
    }
}
