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

namespace Tests\Redis;

use Exception;
use Omega\Application\Application;
use Omega\Redis\RedisInterface;
use Omega\Redis\RedisManager;
use Omega\Redis\RedisServiceProvider;

use function expect;
use function extension_loaded;

covers(RedisServiceProvider::class);

beforeEach(function (): void {
    $this->app = new Application('/');
});

afterEach(function (): void {
    $this->app->flush();
});

it('registers the manager and resolves a configured connection', function (): void {
    if (!extension_loaded('redis')) {
        $this->markTestSkipped('Redis extension not loaded.');
    }

    $this->app->set('config', [
        'redis' => [
            'default'     => 'default',
            'connections' => [
                'default' => [
                    'host'     => '127.0.0.1',
                    'port'     => 6379,
                    'database' => 1,
                ],
            ],
        ],
    ]);

    (new RedisServiceProvider($this->app))->register();

    $manager = $this->app->get(RedisManager::class);
    expect($manager)->toBeInstanceOf(RedisManager::class);

    try {
        $redis = resolveRedisInterface($this->app);
    } catch (Exception) {
        $this->markTestSkipped('Could not connect to Redis server.');
    }

    expect($redis->getName())->toBe('PHPRedis');
    expect($redis->set('provider-key', 'provider-value'))->toBeTrue();
    expect($redis->get('provider-key'))->toBe('provider-value');

    expect($redis->del('provider-key'))->toBe(1);
});

it('defers the connection when no redis configuration is registered', function (): void {
    $this->app->set('config', []);

    (new RedisServiceProvider($this->app))->register();

    expect($this->app->get(RedisManager::class))->toBeInstanceOf(RedisManager::class);

    expect(fn (): mixed => $this->app->get(RedisInterface::class))
        ->toThrow(Exception::class, 'No default Redis connection has been configured.');
});

/**
 * Resolve the Redis connection from the application container.
 *
 * @param Application $app The application instance.
 * @return RedisInterface The resolved Redis connection.
 */
function resolveRedisInterface(Application $app): RedisInterface
{
    $redis = $app->get(RedisInterface::class);

    if (!$redis instanceof RedisInterface) {
        throw new \RuntimeException('The RedisInterface container binding is not a RedisInterface.');
    }

    return $redis;
}