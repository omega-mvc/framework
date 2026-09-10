<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\Dispatcher\Dispatcher;
use Omega\Event\Dispatcher\DispatcherAwareTrait;
use Omega\Event\Exceptions\DispatcherNotSetException;
use Tests\Event\Support\DispatcherAwareService;

covers(DispatcherAwareTrait::class);
covers(DispatcherNotSetException::class);

it('throws when no dispatcher has been set', function (): void {
    $service = new DispatcherAwareService();

    expect(fn () => $service->getDispatcher())->toThrow(DispatcherNotSetException::class);
});

it('sets and returns the dispatcher', function (): void {
    $service    = new DispatcherAwareService();
    $dispatcher = new Dispatcher();

    expect($service->setDispatcher($dispatcher))->toBe($service);
    expect($service->getDispatcher())->toBe($dispatcher);
});

it('returns the most recent dispatcher', function (): void {
    $service     = new DispatcherAwareService();
    $first       = new Dispatcher();
    $second      = new Dispatcher();

    $service->setDispatcher($first);
    $service->setDispatcher($second);

    expect($service->getDispatcher())->toBe($second);
});