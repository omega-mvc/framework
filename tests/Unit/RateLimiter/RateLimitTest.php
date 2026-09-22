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

use DateTime;
use Omega\RateLimiter\RateLimit;

use function expect;

covers(RateLimit::class);

it('exposes the rate limit state with retry and expiration dates', function (): void {
    $retryAfter = new DateTime('@1000');
    $expiresAt  = new DateTime('@2000');

    $rateLimit = new RateLimit(
        identifier: 'user:1',
        limit: 5,
        consumed: 2,
        remaining: 3,
        isBlocked: true,
        retryAfter: $retryAfter,
        expiresAt: $expiresAt,
    );

    expect($rateLimit->getIdentifier())->toBe('user:1');
    expect($rateLimit->getLimit())->toBe(5);
    expect($rateLimit->getConsumed())->toBe(2);
    expect($rateLimit->getRemaining())->toBe(3);
    expect($rateLimit->isBlocked())->toBeTrue();
    expect($rateLimit->getRetryAfter())->toBe($retryAfter);
    expect($rateLimit->getExpiresAt())->toBe($expiresAt);
});

it('defaults retry and expiration dates to null', function (): void {
    $rateLimit = new RateLimit(
        identifier: 'user:1',
        limit: 5,
        consumed: 0,
        remaining: 5,
        isBlocked: false,
    );

    expect($rateLimit->getIdentifier())->toBe('user:1');
    expect($rateLimit->getLimit())->toBe(5);
    expect($rateLimit->getConsumed())->toBe(0);
    expect($rateLimit->getRemaining())->toBe(5);
    expect($rateLimit->isBlocked())->toBeFalse();
    expect($rateLimit->getRetryAfter())->toBeNull();
    expect($rateLimit->getExpiresAt())->toBeNull();
});