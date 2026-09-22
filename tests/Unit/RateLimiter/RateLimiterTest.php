<?php

/**
 * Part of Omega - Tests Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\RateLimiter;

use Omega\Cache\Storage\MemoryStorage;
use Omega\RateLimiter\Policy\FixedWindow;
use Omega\RateLimiter\Policy\NoLimiter;
use Omega\RateLimiter\RateLimiter;

use function expect;

covers(FixedWindow::class);
covers(MemoryStorage::class);
covers(NoLimiter::class);
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

it('is blocked', function (): void {
    expect($this->rateLimiter->isBlocked('key', 1, 1))->toBeFalse();

    $this->rateLimiter->consume('key');

    expect($this->rateLimiter->isBlocked('key', 1, 1))->toBeTrue();
});

it('get retry after when not blocked', function (): void {
    $rateLimiter = new RateLimiter(new NoLimiter());

    expect($rateLimiter->getRetryAfter('key'))->toBe(0);
});

it('get retry after when blocked', function (): void {
    $rateLimiter = new RateLimiter(
        new FixedWindow(
            cache: new MemoryStorage(['ttl' => 3600]),
            limit: 1,
            windowSeconds: 3600
        )
    );

    $rateLimiter->consume('key');

    $retryAfter = $rateLimiter->getRetryAfter('key');

    expect($retryAfter)->toBeGreaterThanOrEqual(0);
    expect($retryAfter)->toBeLessThanOrEqual(3600);
});
