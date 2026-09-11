<?php

/**
 * Part of Omega - Redis Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Redis;

use Omega\Container\AbstractServiceProvider;

/**
 * Registers Redis as an independent database into the application container.
 *
 * This service provider reads the `redis` configuration key and binds the
 * {@see RedisManager} (and its {@see RedisInterface}) so that Redis connections
 * can be resolved lazily by name through the container.
 *
 * @category  Omega
 * @package   Redis
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class RedisServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     *
     * Binds the RedisManager as a shared instance and populates it with the
     * configured Redis connections so they can be resolved by name.
     */
    public function register(): void
    {
        $this->app->set(RedisManager::class, function () {
            /** @var array{redis?: array{default?: string, connections?: array<string, array{host?: string, port?: int, timeout?: float, retry_interval?: int, read_timeout?: float, persistent?: bool, persistent_id?: string, password?: string, database?: int, unix_socket?: string}>}} $config */
            $config = $this->app->get('config');

            $manager = new RedisManager();
            $manager->setConfig($config['redis'] ?? []);

            return $manager;
        });

        $this->app->set(RedisInterface::class, function () {
            /** @var RedisManager $manager */
            $manager = $this->app->get(RedisManager::class);

            return $manager->connection();
        });
    }
}
