<?php

declare(strict_types=1);

namespace WeatherCities\Tests\Unit\Cache;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;
use WeatherCities\Cache\FilesystemCache;
use WeatherCities\Tests\Support\FrozenClock;
use WeatherCities\Tests\Support\TemporaryDirectory;

final class FilesystemCacheTest extends TestCase
{
    private string $directory;
    private FrozenClock $clock;
    private FilesystemCache $cache;

    protected function setUp(): void
    {
        $this->directory = TemporaryDirectory::create();
        $this->clock = new FrozenClock();
        $this->cache = new FilesystemCache($this->directory . '/cache', $this->clock);
    }

    protected function tearDown(): void
    {
        TemporaryDirectory::remove($this->directory);
    }

    public function testStoresAndReadsAValue(): void
    {
        self::assertTrue($this->cache->set('weather', ['temp' => 6.5]));

        self::assertSame(['temp' => 6.5], $this->cache->get('weather'));
        self::assertTrue($this->cache->has('weather'));
    }

    public function testMissReturnsTheDefault(): void
    {
        self::assertSame('fallback', $this->cache->get('missing', 'fallback'));
        self::assertFalse($this->cache->has('missing'));
    }

    public function testEntryExpiresAfterTheTtl(): void
    {
        $this->cache->set('weather', 'sunny', 60);

        $this->clock->advance(59);
        self::assertSame('sunny', $this->cache->get('weather'));

        $this->clock->advance(1);
        self::assertNull($this->cache->get('weather'));
        self::assertSame([], glob($this->directory . '/cache/*.cache'));
    }

    public function testDateIntervalTtl(): void
    {
        $this->cache->set('weather', 'sunny', new \DateInterval('PT2M'));

        $this->clock->advance(119);
        self::assertSame('sunny', $this->cache->get('weather'));
        $this->clock->advance(1);
        self::assertNull($this->cache->get('weather'));
    }

    public function testZeroTtlRemovesTheEntry(): void
    {
        $this->cache->set('weather', 'sunny');
        $this->cache->set('weather', 'rain', 0);

        self::assertFalse($this->cache->has('weather'));
    }

    public function testDeleteAndClear(): void
    {
        $this->cache->setMultiple(['a' => 1, 'b' => 2, 'c' => 3]);

        self::assertTrue($this->cache->delete('a'));
        self::assertSame(['a' => null, 'b' => 2], $this->cache->getMultiple(['a', 'b']));
        self::assertTrue($this->cache->deleteMultiple(['b']));
        self::assertTrue($this->cache->clear());
        self::assertFalse($this->cache->has('c'));
    }

    public function testFilesStayInsideTheDirectory(): void
    {
        $this->cache->set('..%2e%2epasswd', 'x');

        $files = glob($this->directory . '/cache/*');
        self::assertIsArray($files);
        self::assertCount(1, $files);
        self::assertMatchesRegularExpression('~/[0-9a-f]{40}\.cache$~', $files[0]);
        self::assertSame([$this->directory . '/cache'], glob($this->directory . '/*'));
    }

    public function testCorruptFileIsAMiss(): void
    {
        $this->cache->set('weather', 'sunny');
        $files = glob($this->directory . '/cache/*.cache');
        self::assertIsArray($files);
        file_put_contents($files[0], 'not serialized');

        $previous = ini_set('error_log', '/dev/null');
        try {
            self::assertNull($this->cache->get('weather'));
        } finally {
            ini_set('error_log', (string) $previous);
        }
        self::assertFileDoesNotExist($files[0]);
    }

    public function testObjectsAreNotRestored(): void
    {
        $this->cache->set('weather', new \ArrayObject([1]));

        self::assertInstanceOf(\__PHP_Incomplete_Class::class, $this->cache->get('weather'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidKeyProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'path traversal' => ['../etc/passwd'];
        yield 'reserved colon' => ['a:b'];
        yield 'reserved braces' => ['{a}'];
    }

    #[DataProvider('invalidKeyProvider')]
    public function testInvalidKeysAreRejected(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cache->get($key);
    }
}
