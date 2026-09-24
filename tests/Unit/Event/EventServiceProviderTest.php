<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Application\Application;
use Omega\Database\Model\Model;
use Omega\Event\Dispatcher\Dispatcher;
use Omega\Event\Dispatcher\DispatcherInterface;
use Omega\Event\EventServiceProvider;

covers(EventServiceProvider::class);

it('boots and registers the default model dispatcher', function (): void {
    $app      = new Application(__DIR__);
    $provider = new EventServiceProvider($app);

    $provider->boot();

    $dispatcher = $app->make(DispatcherInterface::class);

    expect($dispatcher)->toBeInstanceOf(Dispatcher::class);
    expect(Model::getEventDispatcher())->toBe($dispatcher);
});

it('boots and registers the events alias', function (): void {
    $app      = new Application(__DIR__);
    $provider = new EventServiceProvider($app);

    $provider->boot();

    expect($app->make('events'))->toBeInstanceOf(DispatcherInterface::class);
});