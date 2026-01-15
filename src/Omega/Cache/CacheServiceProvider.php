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
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        $config         = $this->app->get('config');
        $default        = $config['cache.default'];
        $storages       = $config['cache.storage'];

        $options        = $storages[$default];

        $this->app->set('cache.options', $options);

        $cache = match ($default) {
            'memory' => 'cache.memory',
            default  => 'cache.file',
        };

        $this->app->set(
            'cache.file',
            fn(): File => new File($this->app->get('cache.options'))
        );

        $this->app->set(
            'cache.memory',
            fn(): Memory => new Memory($this->app->get('cache.options'))
        );

        $this->app->set('cache', function () use ($cache, $default): CacheFactory {
            return new CacheFactory($default, $this->app[$cache]);
        });
    }
}
