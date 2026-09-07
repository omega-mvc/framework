<?php

declare(strict_types=1);

namespace Tests\RateLimiter;

use Omega\Cache\Storage\MemoryStorage;
use Omega\RateLimiter\RateLimiterFactory;
use Omega\RateLimiter\RateLimiterInterface;

use function expect;

covers(MemoryStorage::class);
covers(RateLimiterFactory::class);

it('can create rate limiter', function (): void {
    $factory = new RateLimiterFactory(new MemoryStorage(['ttl' => 3600]));

    expect($factory->createFixedWindow(10, 60))->toBeInstanceOf(RateLimiterInterface::class);
    expect($factory->createNoLimiter())->toBeInstanceOf(RateLimiterInterface::class);
});
