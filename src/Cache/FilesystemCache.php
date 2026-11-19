<?php

declare(strict_types=1);

namespace WeatherCities\Cache;

use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 cache with one file per key. File names are sha1 hashes of the key, so a key can never point outside the
 * directory. Values are restored with `allowed_classes => false`: a tampered cache file cannot instantiate classes,
 * so store scalars and arrays only.
 */
final readonly class FilesystemCache implements CacheInterface
{
    private const string RESERVED_CHARACTERS = '{}()/\@:';
    private const string SUFFIX = '.cache';

    public function __construct(
        private string $directory,
        private ClockInterface $clock,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $entry = $this->read($this->path($key));

        return $entry === null ? $default : $entry['value'];
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $path = $this->path($key);
        $expiresAt = $this->expiresAt($ttl);
        if ($expiresAt !== null && $expiresAt <= $this->clock->now()->getTimestamp()) {
            return $this->deleteFile($path);
        }
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0o775, true) && !is_dir($this->directory)) {
            return false;
        }

        // Write to a temporary file and rename it, so readers never see a half-written entry.
        $temporary = @tempnam($this->directory, 'tmp');
        if ($temporary === false) {
            return false;
        }
        $payload = serialize(['expiresAt' => $expiresAt, 'value' => $value]);
        if (@file_put_contents($temporary, $payload) === false || !@rename($temporary, $path)) {
            @unlink($temporary);

            return false;
        }

        return true;
    }

    public function delete(string $key): bool
    {
        return $this->deleteFile($this->path($key));
    }

    public function clear(): bool
    {
        $files = glob($this->directory . '/*' . self::SUFFIX);
        if ($files === false) {
            return false;
        }
        $cleared = true;
        foreach ($files as $file) {
            $cleared = $this->deleteFile($file) && $cleared;
        }

        return $cleared;
    }

    /**
     * @param iterable<string> $keys
     *
     * @return array<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * @param iterable<mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $stored = true;
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidCacheKey('Cache keys must be strings.');
            }
            $stored = $this->set($key, $value, $ttl) && $stored;
        }

        return $stored;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $deleted = true;
        foreach ($keys as $key) {
            $deleted = $this->delete($key) && $deleted;
        }

        return $deleted;
    }

    public function has(string $key): bool
    {
        return $this->read($this->path($key)) !== null;
    }

    private function path(string $key): string
    {
        if ($key === '' || strpbrk($key, self::RESERVED_CHARACTERS) !== false) {
            throw new InvalidCacheKey(sprintf('Invalid cache key "%s".', $key));
        }

        return $this->directory . '/' . sha1($key) . self::SUFFIX;
    }

    private function expiresAt(null|int|\DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }
        $now = $this->clock->now();
        if ($ttl instanceof \DateInterval) {
            return $now->add($ttl)->getTimestamp();
        }

        return $now->getTimestamp() + $ttl;
    }

    /**
     * @return array{expiresAt: ?int, value: mixed}|null
     */
    private function read(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }
        $payload = @file_get_contents($path);
        if ($payload === false) {
            return null;
        }
        $entry = @unserialize($payload, ['allowed_classes' => false]);
        $expiresAt = is_array($entry) ? ($entry['expiresAt'] ?? null) : null;
        if (!is_array($entry) || !array_key_exists('value', $entry) || !($expiresAt === null || is_int($expiresAt))) {
            error_log(sprintf('Cache file %s is corrupt and was removed.', $path));
            $this->deleteFile($path);

            return null;
        }
        if ($expiresAt !== null && $expiresAt <= $this->clock->now()->getTimestamp()) {
            $this->deleteFile($path);

            return null;
        }

        return ['expiresAt' => $expiresAt, 'value' => $entry['value']];
    }

    private function deleteFile(string $path): bool
    {
        return !is_file($path) || @unlink($path) || !is_file($path);
    }
}
