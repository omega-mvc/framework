<?php

declare(strict_types=1);

namespace Tests\Container;

use DateTime;
use Omega\Container\Container;
use Omega\Container\ReflectionCache;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use stdClass;
use Tests\TestCase;

#[CoversClass(Container::class)]
#[CoversClass(ReflectionCache::class)]
final class ReflectionCacheTest extends TestCase
{
    private ReflectionCache $cache;

    protected function setUp(): void
    {
        $this->cache = new ReflectionCache();
    }

    public function testGetsAndCachesAReflectionClass(): void
    {
        $callCount = 0;
        $reflect   = static fn (object $class): ReflectionClass => new ReflectionClass($class);
        $creator   = function () use (&$callCount, $reflect): ReflectionClass {
            $callCount++;

            return $reflect(new stdClass());
        };

        $result1 = $this->cache->getReflectionClass(stdClass::class, $creator);
        $result2 = $this->cache->getReflectionClass(stdClass::class, $creator);

        $this->assertSame($result1, $result2);
        $this->assertSame(1, $callCount);
    }

    public function testGetsAndCachesAReflectionMethod(): void
    {
        $callCount = 0;
        $creator   = function () use (&$callCount): ReflectionMethod {
            $callCount++;

            return new ReflectionMethod(DateTime::class, 'getTimestamp');
        };

        $result1 = $this->cache->getReflectionMethod(DateTime::class, 'getTimestamp', $creator);
        $result2 = $this->cache->getReflectionMethod(DateTime::class, 'getTimestamp', $creator);

        $this->assertSame($result1, $result2);
        $this->assertSame(1, $callCount);
    }

    public function testGetsAndCachesConstructorParameters(): void
    {
        $callCount = 0;
        $fixture   = new class {
            public function __construct(
                private int $time = 0,
                private string $name = '',
            ) {
            }

            /**
             * @return array{0: int, 1: string}
             */
            public function signature(): array
            {
                return [$this->time, $this->name];
            }
        };
        $ref         = new ReflectionClass($fixture);
        $constructor = $ref->getConstructor();

        $this->assertNotNull($constructor);

        $params  = $constructor->getParameters();
        $creator = function () use (&$callCount, $params) {
            $callCount++;

            return $params;
        };

        $result1 = $this->cache->getConstructorParameters($fixture::class, $creator);
        $result2 = $this->cache->getConstructorParameters($fixture::class, $creator);

        $this->assertSame($result1, $result2);
        $this->assertSame(1, $callCount);
        $this->assertEquals($params, $result1);
    }

    public function testClearsAllCaches(): void
    {
        $callCount = 0;
        $reflect   = static fn (object $class): ReflectionClass => new ReflectionClass($class);
        $creator   = function () use (&$callCount, $reflect): ReflectionClass {
            $callCount++;

            return $reflect(new stdClass());
        };

        $this->cache->getReflectionClass(stdClass::class, $creator);

        $this->assertSame(1, $callCount);

        $this->cache->clear();

        $this->cache->getReflectionClass(stdClass::class, $creator);

        $this->assertSame(2, $callCount);
    }
}
