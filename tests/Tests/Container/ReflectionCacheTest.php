<?php

/**
 * Part of Omega - Tests\Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Container;

use DateTime;
use Omega\Container\ReflectionCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use stdClass;

/**
 * Class ReflectionCacheTest
 *
 * This test class verifies the behavior of the ReflectionCache utility. It ensures that
 * reflection objects for classes, methods, and constructor parameters are cached correctly
 * to avoid repeated creation, and that caches can be cleared when needed.
 *
 * @category  Tests
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(ReflectionCache::class)]
final class ReflectionCacheTest extends TestCase
{
    /** @var ReflectionCache Cache instance for storing reflection classes, methods, and constructor params */
    private ReflectionCache $cache;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new ReflectionCache();
    }

    /**
     * Test it gets and caches reflection class.
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItGetsAndCachesReflectionClass(): void
    {
        $callCount = 0;
        $creator   = function () use (&$callCount) {
            $callCount++;

            return new ReflectionClass(stdClass::class);
        };

        $result1 = $this->cache->getReflectionClass(stdClass::class, $creator);
        $result2 = $this->cache->getReflectionClass(stdClass::class, $creator);

        $this->assertSame($result1, $result2);
        $this->assertEquals(1, $callCount, 'Creator should only be called once.');
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(ReflectionClass::class, $result1);
    }

    /**
     * Test it gets and caches reflection method.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testItGetsAndCachesReflectionMethod(): void
    {
        $callCount = 0;
        $creator   = function () use (&$callCount) {
            $callCount++;

            return new ReflectionMethod(DateTime::class, 'getTimestamp');
        };

        $result1 = $this->cache->getReflectionMethod(DateTime::class, 'getTimestamp', $creator);
        $result2 = $this->cache->getReflectionMethod(DateTime::class, 'getTimestamp', $creator);

        $this->assertSame($result1, $result2);
        $this->assertEquals(1, $callCount, 'Creator should only be called once.');
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(ReflectionMethod::class, $result1);
    }

    /**
     * Test it gets and caches constructor parameters.
     *
     * @return void
     */
    public function testItGetsAndCachesConstructorParameters(): void
    {
        $callCount = 0;
        $fixture   = new class {
            public function __construct(int $time = 0, string $name = '')
            {
            }
        };
        $ref    = new ReflectionClass($fixture);
        $params = $ref->getConstructor()->getParameters();

        $creator = function () use (&$callCount, $params) {
            $callCount++;

            return $params;
        };

        $result1 = $this->cache->getConstructorParameters($fixture::class, $creator);
        $result2 = $this->cache->getConstructorParameters($fixture::class, $creator);

        $this->assertSame($result1, $result2);
        $this->assertEquals(1, $callCount, 'Creator should only be called once.');
        $this->assertEquals($params, $result1);
    }

    /**
     * Test it clear al caches.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testItClearsAllCaches(): void
    {
        $callCount = 0;
        $creator   = function () use (&$callCount) {
            $callCount++;

            return new ReflectionClass(stdClass::class);
        };

        // Populate the cache
        $this->cache->getReflectionClass(stdClass::class, $creator);
        $this->assertEquals(1, $callCount);

        // Clear the cache
        $this->cache->clear();

        // Try to get the item again
        $this->cache->getReflectionClass(stdClass::class, $creator);
        $this->assertEquals(2, $callCount, 'Creator should be called again after clearing the cache.');
    }
}
