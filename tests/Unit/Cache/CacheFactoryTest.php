<?php

declare(strict_types=1);

namespace Tests\Cache;

use Omega\Cache\CacheInterface;
use Omega\Cache\CacheManager;
use Omega\Cache\Storage\FileStorage;
use Omega\Cache\Storage\MemoryStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\TestCase;

use function Omega\Application\slash;

#[CoversClass(CacheManager::class)]
#[CoversClass(FileStorage::class)]
#[CoversClass(MemoryStorage::class)]
#[CoversFunction('Omega\Application\slash')]
final class CacheFactoryTest extends TestCase
{
    public function testRegistersTheFileStorageDriverViaTheCacheManagerFactory(): void
    {
        $cache = new CacheManager('array1', new FileStorage(['ttl' => 3_600, 'path' => slash(path: '/cache')]));

        $this->assertInstanceOf(CacheInterface::class, $cache->getDriver('array1'));
        $this->assertTrue($cache->getDriver('array1')->set('key1', 'value1'));
        $this->assertEquals('value1', $cache->getDriver('array1')->get('key1'));
    }

    public function testRegistersTheMemoryStorageDriverViaTheCacheManagerFactory(): void
    {
        $cache = new CacheManager('array2', new MemoryStorage(['ttl' => 3_600]));

        $this->assertInstanceOf(CacheInterface::class, $cache->getDriver('array2'));
        $this->assertTrue($cache->getDriver('array2')->set('key1', 'value1'));
        $this->assertEquals('value1', $cache->getDriver('array2')->get('key1'));
    }
}
