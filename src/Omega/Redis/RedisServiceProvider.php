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
            $config = $this->app->get('config')['redis'] ?? [];

            $manager = new RedisManager();
            $manager->setConfig($config);

            return $manager;
        });

        $this->app->set(RedisInterface::class, function () {
            return $this->app->get(RedisManager::class)->connection();
        });
    }
}
