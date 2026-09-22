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
