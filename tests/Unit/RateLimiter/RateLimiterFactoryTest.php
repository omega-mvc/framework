<?php

declare(strict_types=1);

namespace Tests\RateLimiter;

use Omega\Cache\Storage\MemoryStorage;
use Omega\RateLimiter\RateLimiter;
use Omega\RateLimiter\RateLimiterFactory;

use function expect;

covers(MemoryStorage::class);
covers(RateLimiterFactory::class);

it('can create rate limiter', function (): void {
    $factory = new RateLimiterFactory(new MemoryStorage(['ttl' => 3600]));

    expect($factory->createFixedWindow(10, 60))->toBeInstanceOf(RateLimiter::class);
    expect($factory->createNoLimiter())->toBeInstanceOf(RateLimiter::class);
});
