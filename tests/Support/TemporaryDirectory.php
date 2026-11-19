<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Support;

final class TemporaryDirectory
{
    public static function create(): string
    {
        $path = sys_get_temp_dir() . '/weather-cities-' . bin2hex(random_bytes(8));
        if (!mkdir($path, 0o700, true)) {
            throw new \RuntimeException(sprintf('Could not create %s.', $path));
        }

        return $path;
    }

    public static function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            assert($item instanceof \SplFileInfo);
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
