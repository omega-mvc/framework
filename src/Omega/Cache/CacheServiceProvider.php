<?php

/**
 * Part of Omega - Cache Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Cache;

use DateInterval;
use Omega\Cache\Storage\ApcuStorage;
use Omega\Cache\Storage\FileStorage;
use Omega\Cache\Storage\MemcachedStorage;
use Omega\Cache\Storage\MemoryStorage;
use Omega\Cache\Storage\RedisStorage;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\AbstractServiceProvider;
use Omega\Redis\RedisManager;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use RuntimeException;

use function array_keys;

/**
 * Bootstraps the cache system and registers available cache drivers.
 *
 * This service provider is responsible for configuring and initializing
 * all cache storage drivers used by the framework. It determines the
 * default cache driver based on the application's configuration and
 * ensures that the File driver is always available for internal
 * framework operations (e.g. view caching).
 *
 * Behavior:
 * - The default cache driver is selected from the configuration key `cache.default`.
 * - Both "file" and "array" drivers are registered and can be used interchangeably.
 * - If the selected driver is not "file", an additional File instance
 *   is still initialized to ensure that file-based cache operations remain available.
 *
 * Unlike previous versions, this provider does not use `setDefaultDriver()`.
 * Each driver is now explicitly registered through `setDriver()`, and the
 * framework resolves the active driver dynamically from configuration.
 *
 * @category  Omega
 * @package   Cache
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class CacheServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function boot(): void
    {
        /** @var array{
         *     cache: array{default: string, storage: array<string, array{
         *         ttl?: int|DateInterval,
         *         path?: string,
         *         prefix?: string,
         *         host?: string,
         *         port?: int,
         *         connection?: string,
         *         maxItems?: int,
         *         servers?: list<array{host: string, port: int}>,
         *         username?: string,
         *         password?: string,
         *         timeout?: int,
         *     }>},
         *     redis: array{default: string, connections: array<string, array<string, mixed>>},
         * } $config
         */
        $config   = $this->app->get('config');
        $default  = $config['cache']['default'];
        $adapters = $config['cache']['storage'];

        // Registrazione di tutti i driver
        foreach ($adapters as $name => $options) {
            $this->app->set("cache.$name", fn (): CacheInterface => $this->createAdapter($name, $options));
        }

        $this->app->set('cache', function () use ($default, $adapters): CacheManager {
            $manager = new CacheManager($default, $this->createAdapter($default, $adapters[$default]));

            foreach (array_keys($adapters) as $driver) {
                if ($driver !== $default) {
                    $manager->setDriver($driver, fn (): CacheInterface => $this->createAdapter($driver, $adapters[$driver]));
                }
            }

            return $manager;
        });
    }

    /**
     * Create a cache storage adapter from the given driver name and options.
     *
     * @param string $name    The cache driver name.
     * @param array{
     *     ttl?: int|DateInterval,
     *     path?: string,
     *     prefix?: string,
     *     host?: string,
     *     port?: int,
     *     connection?: string,
     *     maxItems?: int,
     *     servers?: list<array{host: string, port: int}>,
     *     username?: string,
     *     password?: string,
     *     timeout?: int,
     * } $options The driver-specific configuration options.
     * @return CacheInterface The instantiated cache storage adapter.
     */
    private function createAdapter(string $name, array $options): CacheInterface
    {
        return match ($name) {
            'apcu'      => new ApcuStorage($options),
            'file'      => new FileStorage($options),
            'memory'    => new MemoryStorage($options),
            'memcached' => new MemcachedStorage($options),
            'redis'     => $this->createRedis($options),
            default     => throw new RuntimeException("Unknown cache adapter: $name"),
        };
    }

    /**
     * Create a Redis-backed cache storage instance.
     *
     * @param array{
     *     ttl?: int|DateInterval,
     *     connection?: string,
     *     path?: string,
     *     prefix?: string,
     *     host?: string,
     *     port?: int,
     *     maxItems?: int,
     *     servers?: list<array{host: string, port: int}>,
     *     username?: string,
     *     password?: string,
     *     timeout?: int,
     * } $options The Redis driver configuration options.
     * @return RedisStorage The resolved Redis cache storage adapter.
     */
    private function createRedis(array $options): RedisStorage
    {
        /** @var array{redis: array{default: string, connections: array<string, array<string, mixed>>}} $config */
        $config = $this->app->get('config');

        $connectionName = $options['connection'] ?? $config['redis']['default'];

        /** @var RedisManager $redisManager */
        $redisManager = $this->app->get(RedisManager::class);

        $connection = $redisManager->connection($connectionName);

        return new RedisStorage($options, $connection);
    }
}
