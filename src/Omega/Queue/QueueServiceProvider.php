<?php

/**
 * Part of Omega - Queue Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Queue;

use Omega\Container\AbstractServiceProvider;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\ConnectionInterface;
use Omega\Database\DatabaseManager;
use Omega\Queue\Adapter\DatabaseQueueAdapter;
use Omega\Queue\Exception\UnknownDriverException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function array_keys;

class QueueServiceProvider extends AbstractServiceProvider
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
        /** @var array{queue?: array{default?: string, drivers?: array<string, array{table?: string}>}} $config */
        $config   = $this->app->get('config');
        $default  = $config['queue']['default'] ?? 'database';
        $adapters = $config['queue']['drivers'] ?? ['database' => ['table' => 'jobs']];

        foreach ($adapters as $name => $options) {
            $this->app->set("queue.adapter.$name", fn (): QueueAdapterInterface => $this->createAdapter($name, $options));
        }

        $this->app->set('queue', function () use ($default, $adapters): QueueManager {
            $manager = new QueueManager($default, $this->createAdapter($default, $adapters[$default] ?? []));

            foreach (array_keys($adapters) as $driver) {
                if ($driver !== $default) {
                    $manager->setDriver(
                        $driver,
                        fn (): QueueAdapterInterface => $this->createAdapter($driver, $adapters[$driver]),
                    );
                }
            }

            return $manager;
        });
    }

    /**
     * @param array{table?: string} $options
     */
    private function createAdapter(string $name, array $options): QueueAdapterInterface
    {
        return match ($name) {
            'database' => new DatabaseQueueAdapter(
                $this->resolveDatabase(),
                $options['table'] ?? 'jobs',
            ),
            default    => throw new UnknownDriverException($name),
        };
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
