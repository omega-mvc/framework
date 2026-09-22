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

use Omega\Application\Application;
use Omega\Cache\CacheManager;
use Omega\Cache\Storage\MemoryStorage;
use Omega\Middleware\ThrottleMiddleware;
use Omega\RateLimiter\RateLimiterFactory;
use Omega\RateLimiter\RateLimiterServiceProvider;

use function expect;

covers(CacheManager::class);
covers(MemoryStorage::class);
covers(RateLimiterFactory::class);
covers(RateLimiterServiceProvider::class);
covers(ThrottleMiddleware::class);

beforeEach(function (): void {
    $this->app = new Application('/');

    $this->app->set('cache', new CacheManager(
        'array',
        new MemoryStorage(['ttl' => 3600])
    ));
});

afterEach(function (): void {
    $this->app->flush();
});

it('boots and resolves the rate limiter factory', function (): void {
    (new RateLimiterServiceProvider($this->app))->boot();

    $factory = $this->app->get(RateLimiterFactory::class);

    expect($factory)->toBeInstanceOf(RateLimiterFactory::class);
    expect($this->app->get(RateLimiterFactory::class))->toBe($factory);
});

it('boots and resolves the throttle middleware', function (): void {
    (new RateLimiterServiceProvider($this->app))->boot();

    $middleware = $this->app->get(ThrottleMiddleware::class);

    expect($middleware)->toBeInstanceOf(ThrottleMiddleware::class);
    expect($this->app->get(ThrottleMiddleware::class))->toBe($middleware);
});