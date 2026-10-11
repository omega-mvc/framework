<?php

declare(strict_types=1);

namespace Tests\Cache\Facade;

use Omega\Cache\Facade\Cache;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Cache::class)]
final class CacheTest extends TestCase
{
    public function testReturnsTheFacadeAccessor(): void
    {
        $this->assertSame('cache', Cache::getFacadeAccessor());
    }
}
