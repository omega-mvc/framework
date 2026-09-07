<?php

declare(strict_types=1);

namespace Tests\RateLimiter\Policy;

use Omega\Cache\Storage\MemoryStorage;
use Omega\RateLimiter\Policy\FixedWindow;

use function expect;
use function floor;
use function Omega\Time\now;

covers(FixedWindow::class);
covers(MemoryStorage::class);

beforeEach(function (): void {
    $this->cache = new MemoryStorage(['ttl' => 3600]);
});

afterEach(function (): void {
    $this->cache->clear();
});

it('can consume tokens within the limit', function (): void {
    $limiter   = new FixedWindow($this->cache, 5, 60);
    $rateLimit = $limiter->consume('test_key');

    expect($rateLimit->isBlocked())->toBeFalse();
    expect($rateLimit->getConsumed())->toBe(1);
    expect($rateLimit->getRemaining())->toBe(4);
});

it('blocks when consuming tokens exceeds the limit', function (): void {
    $limiter = new FixedWindow($this->cache, 5, 60);

    for ($i = 0; $i < 5; $i++) {
        $limiter->consume('test_key');
    }

    $rateLimit = $limiter->consume('test_key');

    expect($rateLimit->isBlocked())->toBeTrue();
    expect($rateLimit->getConsumed())->toBe(5);
    expect($rateLimit->getRemaining())->toBe(0);
});

it('can peek at the rate limit status', function (): void {
    $limiter = new FixedWindow($this->cache, 5, 60);

    $this->cache->set('test_key:fw:' . floor(now()->getTimestamp() / 60), 3);

    $rateLimit = $limiter->peek('test_key');

    expect($rateLimit->isBlocked())->toBeFalse();
    expect($rateLimit->getConsumed())->toBe(3);
    expect($rateLimit->getRemaining())->toBe(2);
});

it('can reset the rate limit', function (): void {
    $limiter = new FixedWindow($this->cache, 5, 60);

    $limiter->consume('test_key');
    $limiter->reset('test_key');

    $rateLimit = $limiter->peek('test_key');

    expect($rateLimit->getConsumed())->toBe(0);
});
