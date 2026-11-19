<?php

declare(strict_types=1);

namespace WeatherCities\Weather;

use Psr\SimpleCache\CacheInterface;
use WeatherCities\Domain\Coordinates;
use WeatherCities\Domain\WeatherData;

/**
 * Decorator: adds caching to any WeatherProvider. Coordinates are rounded to two decimals (about 1 km), so nearby
 * requests share an entry. Failures are not cached; the next call asks the inner provider again.
 */
final readonly class CachedWeatherProvider implements WeatherProvider
{
    private const string KEY_PREFIX = 'weather_';

    public function __construct(
        private WeatherProvider $inner,
        private CacheInterface $cache,
        private int $ttlSeconds,
    ) {
    }

    public function current(Coordinates $coordinates): WeatherData
    {
        $key = self::KEY_PREFIX . sprintf('%.2f_%.2f', $coordinates->latitude, $coordinates->longitude);

        $cached = $this->fromArray($this->cache->get($key));
        if ($cached !== null) {
            return $cached;
        }

        $weather = $this->inner->current($coordinates);
        if (!$this->cache->set($key, $this->toArray($weather), $this->ttlSeconds)) {
            error_log(sprintf('Could not write weather cache entry %s.', $key));
        }

        return $weather;
    }

    /**
     * @return array{tempMin: float, tempMax: float, tempCurrent: float, description: string, icon: string}
     */
    private function toArray(WeatherData $weather): array
    {
        return [
            'tempMin' => $weather->tempMin,
            'tempMax' => $weather->tempMax,
            'tempCurrent' => $weather->tempCurrent,
            'description' => $weather->description,
            'icon' => $weather->icon,
        ];
    }

    private function fromArray(mixed $data): ?WeatherData
    {
        if (!is_array($data)
            || !is_float($data['tempMin'] ?? null)
            || !is_float($data['tempMax'] ?? null)
            || !is_float($data['tempCurrent'] ?? null)
            || !is_string($data['description'] ?? null)
            || !is_string($data['icon'] ?? null)
        ) {
            return null;
        }

        return new WeatherData($data['tempMin'], $data['tempMax'], $data['tempCurrent'], $data['description'], $data['icon']);
    }
}
