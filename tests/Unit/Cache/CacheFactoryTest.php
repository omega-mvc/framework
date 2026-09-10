<?php

/**
 * Part of Omega - Tests\Cache Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Cache;

use Omega\Cache\CacheInterface;
use Omega\Cache\CacheManager;
use Omega\Cache\Storage\FileStorage;
use Omega\Cache\Storage\MemoryStorage;

use function Omega\Application\slash;

covers(
    CacheManager::class,
    FileStorage::class,
    MemoryStorage::class,
    'Omega\Application\slash',
);

it('registers the file storage driver via the cache manager factory', function (): void {
    $cache = new CacheManager('array1', new FileStorage(['ttl' => 3_600, 'path' => slash(path: '/cache')]));

    $this->assertInstanceOf(CacheInterface::class, $cache->getDriver('array1'));
    expect($cache->getDriver('array1')->set('key1', 'value1'))->toBeTrue();
    expect($cache->getDriver('array1')->get('key1'))->toEqual('value1');
});

it('registers the memory storage driver via the cache manager factory', function (): void {
    $cache = new CacheManager('array2', new MemoryStorage(['ttl' => 3_600]));

    $this->assertInstanceOf(CacheInterface::class, $cache->getDriver('array2'));
    expect($cache->getDriver('array2')->set('key1', 'value1'))->toBeTrue();
    expect($cache->getDriver('array2')->get('key1'))->toEqual('value1');
});