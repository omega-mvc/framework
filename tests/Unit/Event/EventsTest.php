<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\Events\ExceptionEvent;
use Omega\Event\Events\LogEvent;
use Omega\Event\Events\ModelEvent;
use Omega\Event\Events\RouteEvent;
use Tests\Event\Support\FakeConnection;
use Tests\Event\Support\StubModel;

covers(RouteEvent::class);
covers(LogEvent::class);
covers(ExceptionEvent::class);
covers(ModelEvent::class);

it('creates a route before event', function (): void {
    $event = RouteEvent::before('/home', 'GET');

    expect($event->getName())->toBe('route.before');
    expect($event->getArgument('uri'))->toBe('/home');
    expect($event->getArgument('method'))->toBe('GET');
});

it('creates a route after event', function (): void {
    $event = RouteEvent::after('/home', 'GET', fn (): string => 'ok', ['id' => 1]);

    expect($event->getName())->toBe('route.after');
    expect($event->getArgument('uri'))->toBe('/home');
    expect($event->getArgument('method'))->toBe('GET');
    expect($event->getArgument('callable'))->toBeCallable();
    expect($event->getArgument('parameters'))->toEqual(['id' => 1]);
});

it('creates a log written event', function (): void {
    $event = LogEvent::written('info', 'An event happened', ['user' => 1]);

    expect($event->getName())->toBe('log.written');
    expect($event->getArgument('level'))->toBe('info');
    expect($event->getArgument('message'))->toBe('An event happened');
    expect($event->getArgument('context'))->toEqual(['user' => 1]);
});

it('creates an exception logged event', function (): void {
    $exception = new \RuntimeException('boom');
    $event     = ExceptionEvent::create($exception, 'critical');

    expect($event->getName())->toBe('exception.logged');
    expect($event->getArgument('exception'))->toBe($exception);
    expect($event->getArgument('level'))->toBe('critical');
    expect($event->getArgument('message'))->toBe('boom');
    expect($event->getArgument('class'))->toBe(\RuntimeException::class);
});

it('creates a model created event', function (): void {
    $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');
    $event = ModelEvent::created($model);

    expect($event->getName())->toBe('model.created');
    expect($event->getArgument('model'))->toBe($model);
    expect($event->getArgument('table'))->toBe('stub_table');
});

it('creates a model saved event', function (): void {
    $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');
    $event = ModelEvent::saved($model, true);

    expect($event->getName())->toBe('model.saved');
    expect($event->getArgument('model'))->toBe($model);
    expect($event->getArgument('table'))->toBe('stub_table');
    expect($event->getArgument('created'))->toBeTrue();
});

it('creates a model deleted event', function (): void {
    $model = new StubModel(new FakeConnection(), [['id' => 1]], 'stub_table');
    $event = ModelEvent::deleted($model);

    expect($event->getName())->toBe('model.deleted');
    expect($event->getArgument('model'))->toBe($model);
    expect($event->getArgument('table'))->toBe('stub_table');
});