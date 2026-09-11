<?php

declare(strict_types=1);

namespace Tests\RateLimiter\Policy;

use Omega\RateLimiter\Policy\NoLimiter;

use function expect;

use const PHP_INT_MAX;

covers(NoLimiter::class);

it('can consume tokens within the limit', function (): void {
    $limiter   = new NoLimiter();
    $rateLimit = $limiter->consume('test_key');

    expect($rateLimit->isBlocked())->toBeFalse();
    expect($rateLimit->getConsumed())->toBe(0);
    expect($rateLimit->getRemaining())->toBe(PHP_INT_MAX);
});

it('never blocks when consuming tokens exceeds the limit', function (): void {
    $limiter = new NoLimiter();

    for ($i = 0; $i < 5; $i++) {
        $rateLimit = $limiter->consume('test_key');

        expect($rateLimit->isBlocked())->toBeFalse();
        expect($rateLimit->getConsumed())->toBe(0);
        expect($rateLimit->getRemaining())->toBe(PHP_INT_MAX);
    }
});

it('can reset the rate limit', function (): void {
    $limiter = new NoLimiter();

    $rateLimit = $limiter->consume('test_key');

    expect($rateLimit->isBlocked())->toBeFalse();

    $limiter->reset('test_key');

    $rateLimit = $limiter->peek('test_key');

    expect($rateLimit->getConsumed())->toBe(0);
});
