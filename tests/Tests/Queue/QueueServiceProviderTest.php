<?php

declare(strict_types=1);

namespace Tests\Queue;

use Omega\Application\Application;
use Omega\Database\DatabaseManager;
use Omega\Queue\Adapter\DatabaseQueueAdapter;
use Omega\Queue\Exception\UnknownDriverException;
use Omega\Queue\QueueManager;
use Omega\Queue\QueueServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\TestCase;

#[CoversClass(QueueServiceProvider::class)]
final class QueueServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        $this->app = new Application('/');
    }

    protected function tearDown(): void
    {
        $this->app->flush();
    }

    public function testRegistersTheDefaultDatabaseAdapter(): void
    {
        $connection = new ScriptedConnection();

        $this->app->set('config', []);
        $this->app->set(DatabaseManager::class, $connection);

        (new QueueServiceProvider($this->app))->boot();

        $this->assertInstanceOf(DatabaseQueueAdapter::class, $this->app->get('queue.adapter.database'));

        $queue = $this->app->get('queue');

        $this->assertInstanceOf(QueueManager::class, $queue);
        $this->assertSame(['database'], $queue->getDriverNames());
        $this->assertSame('database', $queue->getDefaultDriverName());
    }

    public function testRegistersEveryConfiguredDriver(): void
    {
        $connection = new ScriptedConnection();

        $this->app->set('config', [
            'queue' => [
                'default' => 'database',
                'drivers' => [
                    'database'  => ['table' => 'queued_jobs'],
                    'secondary' => [],
                ],
            ],
        ]);
        $this->app->set(DatabaseManager::class, $connection);

        (new QueueServiceProvider($this->app))->boot();

        $queue = $this->app->get('queue');

        $this->assertInstanceOf(QueueManager::class, $queue);
        $this->assertSame(['database', 'secondary'], $queue->getDriverNames());

        $adapter = $this->app->get('queue.adapter.database');

        $this->assertInstanceOf(DatabaseQueueAdapter::class, $adapter);
        $adapter->push(static fn (): int => 42);

        $this->assertStringContainsString('INSERT INTO queued_jobs', $connection->queries[0]);
    }

    public function testThrowsWhenAnUnknownDriverIsRequested(): void
    {
        $this->app->set('config', [
            'queue' => [
                'default' => 'database',
                'drivers' => ['unknown' => []],
            ],
        ]);
        $this->app->set(DatabaseManager::class, new ScriptedConnection());

        (new QueueServiceProvider($this->app))->boot();

        $this->expectException(UnknownDriverException::class);

        $this->app->get('queue.adapter.unknown');
    }

    public function testThrowsWhenTheDatabaseManagerIsNotAConnection(): void
    {
        $this->app->set('config', []);
        $this->app->set(DatabaseManager::class, new stdClass());

        (new QueueServiceProvider($this->app))->boot();

        $this->expectException(UnknownDriverException::class);
        $this->expectExceptionMessage('database');

        $this->app->get('queue.adapter.database');
    }
}
