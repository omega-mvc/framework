<?php

declare(strict_types=1);

namespace Tests\Cache;

use Omega\Cache\CacheManager;
use Omega\Cache\Exceptions\UnknownStorageException;
use Omega\Cache\Storage\MemoryStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(CacheManager::class)]
final class CacheManagerTest extends TestCase
{
    private CacheManager $manager;

    protected function setUp(): void
    {
        $this->manager = new CacheManager('memory', new MemoryStorage(['ttl' => 3600]));
    }

    public function testReturnsTheDefaultDriverName(): void
    {
        $this->assertSame('memory', $this->manager->getDefaultDriverName());
    }

    public function testReturnsTheRegisteredDriverNames(): void
    {
        $this->manager->setDriver('array', new MemoryStorage(['ttl' => 3600]));

        $this->assertSame(['memory', 'array'], $this->manager->getDriverNames());
    }

    public function testSetsTheDefaultDriverAndReturnsItself(): void
    {
        $driver = new MemoryStorage(['ttl' => 3600]);

        $this->assertSame($this->manager, $this->manager->setDefaultDriver($driver));
        $this->assertSame($driver, $this->manager->getDriver());
    }

    public function testRegistersAndResolvesARegisteredDriver(): void
    {
        $driver = new MemoryStorage(['ttl' => 3600]);

        $this->assertSame($this->manager, $this->manager->setDriver('array', $driver));
        $this->assertSame($driver, $this->manager->getDriver('array'));
    }

    public function testResolvesAClosureDriverOnlyOnce(): void
    {
        $calls = 0;

        $this->manager->setDriver('lazy', function () use (&$calls): MemoryStorage {
            $calls++;

            return new MemoryStorage(['ttl' => 3600]);
        });

        $first = $this->manager->getDriver('lazy');
        $first->set('probe', 'cached');

        $this->assertSame(1, $calls);
        $this->assertSame('cached', $this->manager->getDriver('lazy')->get('probe'));
    }

    public function testFallsBackToTheDefaultDriverWhenNoNameIsGiven(): void
    {
        $this->assertSame($this->manager->getDriver(), $this->manager->getDriver(null));
    }

    public function testThrowsWhenAnUnknownDriverIsRequested(): void
    {
        $this->expectException(UnknownStorageException::class);

        $this->manager->getDriver('missing');
    }

    public function testMagicCallDelegatesToTheDefaultDriver(): void
    {
        $this->manager->set('foo', 'bar');

        $this->assertIsArray($this->callMagicMethod('getInfo', 'foo'));
    }

    private function callMagicMethod(string $method, string $argument): mixed
    {
        return $this->manager->{$method}($argument);
    }

    public function testDelegatesGetAndSet(): void
    {
        $this->assertTrue($this->manager->set('foo', 'bar'));
        $this->assertSame('bar', $this->manager->get('foo'));
    }

    public function testDelegatesGetWithADefault(): void
    {
        $this->assertSame('fallback', $this->manager->get('missing', 'fallback'));
    }

    public function testDelegatesDelete(): void
    {
        $this->manager->set('foo', 'bar');

        $this->assertTrue($this->manager->delete('foo'));
        $this->assertFalse($this->manager->has('foo'));
    }

    public function testDelegatesClear(): void
    {
        $this->assertTrue($this->manager->clear());
    }

    public function testDelegatesGetMultiple(): void
    {
        $this->manager->setMultiple(['a' => 1, 'b' => 2]);

        $result = [];
        foreach ($this->manager->getMultiple(['a', 'b']) as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame(['a' => 1, 'b' => 2], $result);
    }

    public function testDelegatesSetMultiple(): void
    {
        $this->assertTrue($this->manager->setMultiple(['a' => 1, 'b' => 2]));
    }

    public function testDelegatesDeleteMultiple(): void
    {
        $this->manager->setMultiple(['a' => 1, 'b' => 2]);

        $this->assertTrue($this->manager->deleteMultiple(['a', 'b']));
    }

    public function testDelegatesHas(): void
    {
        $this->manager->set('foo', 'bar');

        $this->assertTrue($this->manager->has('foo'));
        $this->assertFalse($this->manager->has('missing'));
    }

    public function testDelegatesIncrementAndDecrement(): void
    {
        $this->manager->set('counter', 5);

        $this->assertSame(6, $this->manager->increment('counter', 1));
        $this->assertSame(4, $this->manager->decrement('counter', 2));
    }

    public function testDelegatesRemember(): void
    {
        $this->assertSame('computed', $this->manager->remember('remembered', fn (): string => 'computed', 3600));
    }
}
