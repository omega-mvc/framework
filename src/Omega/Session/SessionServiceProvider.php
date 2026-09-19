<?php

/**
 * Part of Omega - Session Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Session;

use Omega\Cache\CacheInterface;
use Omega\Container\AbstractServiceProvider;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\ConnectionInterface;
use Omega\Database\DatabaseManager;
use Omega\Middleware\StartSessionMiddleware;
use Omega\Session\Exceptions\UnknownDriverException;
use Omega\Session\Storage\ArrayStorage;
use Omega\Session\Storage\CacheStorage;
use Omega\Session\Storage\DatabaseStorage;
use Omega\Session\Storage\NativeStorage;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function array_walk;

/**
 * Registers session drivers and the SessionManager into the container.
 *
 * Configuration keys (under `session`):
 * - `default`: string — the name of the default session driver.
 * - `drivers`: array<string, array<string, mixed>> — driver-specific options.
 *
 * @category  Omega
 * @package   Session
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class SessionServiceProvider extends AbstractServiceProvider
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
        /** @var array{session?: array{default?: string, drivers?: array<string, array{prefix?: string, table?: string, name?: string}>}} $config */
        $config  = $this->app->get('config');
        $default = $config['session']['default'] ?? 'native';
        $drivers = $config['session']['drivers'] ?? [];

        array_walk($drivers, function (array $options, string $name): void {
            $this->app->set(
                "session.storage.$name",
                fn (): StorageInterface => $this->createDriver($name, $options),
            );
        });

        $this->app->set('session', function () use ($default, $drivers): SessionManager {
            $manager = new SessionManager(
                $default,
                $this->createDriver($default, $drivers[$default] ?? []),
            );

            array_walk($drivers, function (array $options, string $driver) use ($default, $manager): void {
                if ($driver !== $default) {
                    $manager->setDriver(
                        $driver,
                        fn (): StorageInterface => $this->createDriver($driver, $options),
                    );
                }
            });

            return $manager;
        });

        $this->app->set(StartSessionMiddleware::class, function (): StartSessionMiddleware {
            $session = $this->app->get('session');

            if (!$session instanceof SessionManager) {
                throw new UnknownDriverException('session');
            }

            return new StartSessionMiddleware($session);
        });
    }

    /**
     * @param array{prefix?: string, table?: string, name?: string} $options
     */
    private function createDriver(string $name, array $options): StorageInterface
    {
        return match ($name) {
            'array'    => new ArrayStorage(),
            'native'   => new NativeStorage($options),
            'cache'    => new CacheStorage($this->resolveCache(), $options['prefix'] ?? 'session_'),
            'database' => new DatabaseStorage($this->resolveDatabase(), $options['table'] ?? 'sessions'),
            default    => throw new UnknownDriverException($name),
        };
    }

    private function resolveCache(): CacheInterface
    {
        $cache = $this->app->get('cache');

        if (!$cache instanceof CacheInterface) {
            throw new UnknownDriverException('cache');
        }

        return $cache;
    }

    private function resolveDatabase(): ConnectionInterface
    {
        $database = $this->app->get(DatabaseManager::class);

        if (!$database instanceof ConnectionInterface) {
            throw new UnknownDriverException('database');
        }

        return $database;
    }
}
