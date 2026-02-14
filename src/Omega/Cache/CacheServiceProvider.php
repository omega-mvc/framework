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

use Omega\Cache\Storage\File;
use Omega\Cache\Storage\Memory;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Provider\AbstractServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use RuntimeException;

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
        $config   = $this->app->get('config')['cache'];
        $default  = $config['default'];
        $adapters = $config['storage'];

        // Registrazione di tutti i driver
        foreach ($adapters as $name => $options) {
            $this->app->set("cache.$name", function () use ($name, $options) {
                return match ($name) {
                    //'apcu'      => new Apcu($options),
                    'file'      => new File($options),
                    'memory'    => new Memory($options),
                    //'memcached' => $this->createMemcachedAdapter($options),
                    //'redis'     => $this->createRedisAdapter($options),
                    default     => throw new RuntimeException("Unknown cache adapter: $name"),
                };
            });
        }

        // CacheManager principale con driver di default
        $this->app->set('cache', function () use ($default, $adapters) {
            $manager = new CacheManager($default, $this->app["cache.$default"]);

            // Registriamo tutti gli altri driver
            foreach (array_keys($adapters) as $driver) {
                if ($driver !== $default) {
                    $manager->setDriver($driver, $this->app["cache.$driver"]);
                }
            }

            return $manager;
        });
    }

    /**
     * Crea l'adapter Memcached con oggetto Memcached già configurato.
     */
    protected function createMemcachedAdapter(array $options): Memcached
    {
        $memcached = new PhpMemcached();
        $host = $options['host'] ?? '127.0.0.1';
        $port = $options['port'] ?? 11211;
        $memcached->addServer($host, $port);

        return new Memcached($memcached, $options);
    }

    // Dentro CacheServiceProvider

    protected function createRedisAdapter(array $options): Redis
    {
        $client = new PhpRedis();

        $host = $options['host'] ?? '127.0.0.1';
        $port = $options['port'] ?? 6379;
        $timeout = $options['timeout'] ?? 0.0;

        $client->connect($host, $port, $timeout);

        if (!empty($options['password'])) {
            $client->auth($options['password']);
        }

        if (!empty($options['database'])) {
            $client->select((int) $options['database']);
        }

        return new Redis($client, $options);
    }
}
