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
use Tests\Event\Support\FakeConnection;
use Tests\Event\Support\MemoryLogger;
use Tests\Event\Support\StubModel;

use function array_map;

covers(AuditTrailListener::class);
covers(CacheClearListener::class);
covers(ExceptionHandlerListener::class);

it('logs model lifecycle operations', function (): void {
    $logger   = new MemoryLogger();
    $listener = new AuditTrailListener();
    $listener->setLogger($logger);
    $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

    $listener->onModelCreated(ModelEvent::created($model));
    $listener->onModelSaved(ModelEvent::saved($model));
    $listener->onModelDeleted(ModelEvent::deleted($model));

    expect($logger->getRecords())->toHaveCount(3);

    $messages = array_map(
        static fn (array $record): string => $record['message'],
        $logger->getRecords(),
    );

    expect($messages)->toEqual([
        'Model created in table `stub_table`',
        'Model updated in table `stub_table`',
        'Model deleted in table `stub_table`',
    ]);
    expect($logger->getRecords()[0]['context'])->toHaveKey('table');
    expect($logger->getRecords()[0]['context']['table'])->toBe('stub_table');
});

it('marks a saved operation as created', function (): void {
    $logger   = new MemoryLogger();
    $listener = new AuditTrailListener();
    $listener->setLogger($logger);
    $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

    $listener->onModelSaved(ModelEvent::saved($model, true));

    expect($logger->getRecords())->toHaveCount(1);
    expect($logger->getRecords()[0]['message'])->toBe('Model created in table `stub_table`');
});

it('does nothing without a logger', function (): void {
    $listener = new AuditTrailListener();
    $model    = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

    expect(fn () => $listener->onModelCreated(ModelEvent::created($model)))->not->toThrow(\Throwable::class);
});

it('logs caught exceptions', function (): void {
    $logger   = new MemoryLogger();
    $listener = new ExceptionHandlerListener();
    $listener->setLogger($logger);
    $exception = new \RuntimeException('boom');

    $listener->onExceptionLogged(ExceptionEvent::create($exception, 'critical'));

    expect($logger->getRecords())->toHaveCount(1);
    expect($logger->getRecords()[0]['message'])->toBe('Exception caught: boom');
    expect($logger->getRecords()[0]['level'])->toBe('critical');
    expect($logger->getRecords()[0]['context'])->toHaveKey('exception');
    expect($logger->getRecords()[0]['context'])->toHaveKey('file');
    expect($logger->getRecords()[0]['context'])->toHaveKey('line');
});

it('does not log exceptions without a logger', function (): void {
    $listener  = new ExceptionHandlerListener();
    $exception = new \RuntimeException('boom');

    expect(fn () => $listener->onExceptionLogged(ExceptionEvent::create($exception, 'critical')))
        ->not->toThrow(\Throwable::class);
});

it('clears the table cache on save', function (): void {
    $storage  = new MemoryStorage(['ttl' => 3600]);
    $cache    = new CacheManager('memory', $storage);
    $listener = new CacheClearListener();
    $listener->setCacheManager($cache);
    $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

    $cache->set('model.stub_table', 'cached');

    expect($cache->has('model.stub_table'))->toBeTrue();

    $listener->onModelSaved(ModelEvent::saved($model));

    expect($cache->has('model.stub_table'))->toBeFalse();
});

it('clears the table cache on delete', function (): void {
    $storage  = new MemoryStorage(['ttl' => 3600]);
    $cache    = new CacheManager('memory', $storage);
    $listener = new CacheClearListener();
    $listener->setCacheManager($cache);
    $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

    $cache->set('model.stub_table', 'cached');
    $listener->onModelDeleted(ModelEvent::deleted($model));

    expect($cache->has('model.stub_table'))->toBeFalse();
});

it('skips clearing when the event has no table', function (): void {
    $storage  = new MemoryStorage(['ttl' => 3600]);
    $cache    = new CacheManager('memory', $storage);
    $listener = new CacheClearListener();
    $listener->setCacheManager($cache);

    $cache->set('model.other', 'value');
    $listener->onModelSaved(new Event('model.saved'));

    expect($cache->has('model.other'))->toBeTrue();
});

it('does nothing without a cache manager', function (): void {
    $listener = new CacheClearListener();
    $model    = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');

    expect(fn () => $listener->onModelSaved(ModelEvent::saved($model)))->not->toThrow(\Throwable::class);
});