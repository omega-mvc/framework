<?php

declare(strict_types=1);

namespace Tests\RateLimiter;

use Omega\Cache\Storage\MemoryStorage;
use Omega\RateLimiter\Policy\FixedWindow;
use Omega\RateLimiter\RateLimiter;

use function expect;

covers(FixedWindow::class);
covers(MemoryStorage::class);
covers(RateLimiter::class);

beforeEach(function (): void {
    $this->rateLimiter = new RateLimiter(
        new FixedWindow(
            cache: new MemoryStorage(['ttl' => 3600]),
            limit: 1,
            windowSeconds: 60
        )
    );
});

it('consume', function (): void {
    expect($this->rateLimiter->consume('key'))->toBe(1);
});

it('get count left', function (): void {
    expect($this->rateLimiter->getCount('key'))->toBe(0);

    $this->rateLimiter->consume('key');

    expect($this->rateLimiter->getCount('key'))->toBe(1);
});

it('remaining', function (): void {
    expect($this->rateLimiter->remaining('key', 1))->toBe(1);

    $this->rateLimiter->consume('key');

    expect($this->rateLimiter->remaining('key', 1))->toBe(0);
});

it('reset', function (): void {
    $this->rateLimiter->consume('key');
    $this->rateLimiter->reset('key');

    expect($this->rateLimiter->getCount('key'))->toBe(0);
});