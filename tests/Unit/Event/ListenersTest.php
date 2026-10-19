<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Cache\CacheManager;
use Omega\Cache\Storage\MemoryStorage;
use Omega\Event\Event;
use Omega\Event\Events\ExceptionEvent;
use Omega\Event\Events\ModelEvent;
use Omega\Event\Listeners\AuditTrailListener;
use Omega\Event\Listeners\CacheClearListener;
use Omega\Event\Listeners\ExceptionHandlerListener;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\Event\Support\FakeConnection;
use Tests\Event\Support\MemoryLogger;
use Tests\Event\Support\StubModel;
use Tests\TestCase;

use function array_map;

#[CoversClass(AuditTrailListener::class)]
#[CoversClass(CacheClearListener::class)]
#[CoversClass(ExceptionHandlerListener::class)]
final class ListenersTest extends TestCase
{
    public function testLogsModelLifecycleOperations(): void
    {
        $logger   = new MemoryLogger();
        $listener = new AuditTrailListener();
        $listener->setLogger($logger);
        $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

        $listener->onModelCreated(ModelEvent::created($model));
        $listener->onModelSaved(ModelEvent::saved($model));
        $listener->onModelDeleted(ModelEvent::deleted($model));

        $this->assertCount(3, $logger->getRecords());

        $messages = array_map(
            static fn (array $record): string => $record['message'],
            $logger->getRecords(),
        );

        $this->assertEquals([
            'Model created in table `stub_table`',
            'Model updated in table `stub_table`',
            'Model deleted in table `stub_table`',
        ], $messages);
        $this->assertArrayHasKey('table', $logger->getRecords()[0]['context']);
        $this->assertSame('stub_table', $logger->getRecords()[0]['context']['table']);
    }

    public function testMarksASavedOperationAsCreated(): void
    {
        $logger   = new MemoryLogger();
        $listener = new AuditTrailListener();
        $listener->setLogger($logger);
        $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

        $listener->onModelSaved(ModelEvent::saved($model, true));

        $this->assertCount(1, $logger->getRecords());
        $this->assertSame('Model created in table `stub_table`', $logger->getRecords()[0]['message']);
    }

    public function testDoesNothingWithoutALogger(): void
    {
        $listener = new AuditTrailListener();
        $model    = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

        $listener->onModelCreated(ModelEvent::created($model));

        $this->addToAssertionCount(1);
    }

    public function testLogsCaughtExceptions(): void
    {
        $logger   = new MemoryLogger();
        $listener = new ExceptionHandlerListener();
        $listener->setLogger($logger);
        $exception = new RuntimeException('boom');

        $listener->onExceptionLogged(ExceptionEvent::create($exception, 'critical'));

        $this->assertCount(1, $logger->getRecords());
        $this->assertSame('Exception caught: boom', $logger->getRecords()[0]['message']);
        $this->assertSame('critical', $logger->getRecords()[0]['level']);
        $this->assertArrayHasKey('exception', $logger->getRecords()[0]['context']);
        $this->assertArrayHasKey('file', $logger->getRecords()[0]['context']);
        $this->assertArrayHasKey('line', $logger->getRecords()[0]['context']);
    }

    public function testSkipsLoggingWhenTheLoggedValueIsNotAThrowable(): void
    {
        $logger   = new MemoryLogger();
        $listener = new ExceptionHandlerListener();
        $listener->setLogger($logger);

        $event = new Event('exception.logged');
        $event->setArgument('exception', 'plain-string');

        $listener->onExceptionLogged($event);

        $this->assertSame([], $logger->getRecords());
    }

    public function testDoesNotLogExceptionsWithoutALogger(): void
    {
        $listener  = new ExceptionHandlerListener();
        $exception = new RuntimeException('boom');

        $listener->onExceptionLogged(ExceptionEvent::create($exception, 'critical'));

        $this->addToAssertionCount(1);
    }

    public function testClearsTheTableCacheOnSave(): void
    {
        $storage  = new MemoryStorage(['ttl' => 3600]);
        $cache    = new CacheManager('memory', $storage);
        $listener = new CacheClearListener();
        $listener->setCacheManager($cache);
        $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

        $cache->set('model.stub_table', 'cached');

        $this->assertTrue($cache->has('model.stub_table'));

        $listener->onModelSaved(ModelEvent::saved($model));

        $this->assertFalse($cache->has('model.stub_table'));
    }

    public function testClearsTheTableCacheOnDelete(): void
    {
        $storage  = new MemoryStorage(['ttl' => 3600]);
        $cache    = new CacheManager('memory', $storage);
        $listener = new CacheClearListener();
        $listener->setCacheManager($cache);
        $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

        $cache->set('model.stub_table', 'cached');
        $listener->onModelDeleted(ModelEvent::deleted($model));

        $this->assertFalse($cache->has('model.stub_table'));
    }

    public function testSkipsClearingWhenTheEventHasNoTable(): void
    {
        $storage  = new MemoryStorage(['ttl' => 3600]);
        $cache    = new CacheManager('memory', $storage);
        $listener = new CacheClearListener();
        $listener->setCacheManager($cache);

        $cache->set('model.other', 'value');
        $listener->onModelSaved(new Event('model.saved'));

        $this->assertTrue($cache->has('model.other'));
    }

    public function testSkipsClearingWhenTheEventTableIsNotAString(): void
    {
        $storage  = new MemoryStorage(['ttl' => 3600]);
        $cache    = new CacheManager('memory', $storage);
        $listener = new CacheClearListener();
        $listener->setCacheManager($cache);

        $cache->set('model.other', 'value');
        $event = new Event('model.saved');
        $event->setArgument('table', 123);
        $listener->onModelSaved($event);

        $this->assertTrue($cache->has('model.other'));
    }

    public function testDoesNothingWithoutACacheManager(): void
    {
        $listener = new CacheClearListener();
        $model    = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

        $listener->onModelSaved(ModelEvent::saved($model));

        $this->addToAssertionCount(1);
    }

    public function testSubscribesToModelLifecycleEvents(): void
    {
        $this->assertEquals([
            'model.created' => ['onModelCreated', 0],
            'model.saved'   => ['onModelSaved', 0],
            'model.deleted' => ['onModelDeleted', 0],
        ], AuditTrailListener::getSubscribedEvents());
        $this->assertEquals([
            'model.saved'   => ['onModelSaved', 0],
            'model.deleted' => ['onModelDeleted', 0],
        ], CacheClearListener::getSubscribedEvents());
        $this->assertEquals([
            'exception.logged' => ['onExceptionLogged', 10],
        ], ExceptionHandlerListener::getSubscribedEvents());
    }

    public function testFallsBackToTheUnknownTableNameForANonStringTable(): void
    {
        $logger   = new MemoryLogger();
        $listener = new AuditTrailListener();
        $listener->setLogger($logger);
        $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

        $event = new Event('model.saved');
        $event->setArgument('model', $model);
        $event->setArgument('table', 123);

        $listener->onModelSaved($event);

        $this->assertCount(1, $logger->getRecords());
        $this->assertSame('Model updated in table `unknown`', $logger->getRecords()[0]['message']);
    }
}
