<?php

/**
 * Part of Omega - RateLimiter Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\RateLimiter;

use Omega\Cache\CacheManager;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Provider\AbstractServiceProvider;
use Omega\Middleware\ThrottleMiddleware;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

/**
 * Service provider responsible for registering the RateLimiter and ThrottleMiddleware
 * into the application container.
 *
 * This provider sets up the RateLimiterFactory with a cache instance and binds
 * the ThrottleMiddleware using a fixed window rate limiter.
 *
 * @category  Omega
 * @package   RateLimiter
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class RateLimiterServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     *
     * Registers the rate limiter resolver and the throttle middleware.
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        $this->registerRateLimiterResolver();
        $this->registerThrottleMiddleware();
    }

    /**
     * Register the RateLimiterFactory in the application container.
     *
     * Retrieves the cache service from the container, creates a RateLimiterFactory
     * using it, and binds the factory instance for later use.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function registerRateLimiterResolver(): void
    {
        /** @var CacheManager $cache */
        $cache = $this->app->get('cache');
        $rate  = new RateLimiterFactory($cache);

        $this->app->set(RateLimiterFactory::class, fn () => $rate);
    }

    /**
     * Register the ThrottleMiddleware in the application container.
     *
     * Retrieves the RateLimiterFactory from the container and creates a
     * ThrottleMiddleware instance using a fixed window rate limiter with
     * a limit of 60 requests per 60 seconds.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    protected function registerThrottleMiddleware(): void
    {
        $rate = $this->app[RateLimiterFactory::class];
        $this->app->set(ThrottleMiddleware::class, fn() => new ThrottleMiddleware(
            limiter: $rate->createFixedWindow(
                limit: 60,
                windowSeconds: 60,
            )
        ));
    }
}
