<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Container\Container;
use Omega\Event\Event;
use Omega\Event\Exceptions\InvalidServiceListenerException;
use Omega\Event\Exceptions\InvalidServiceMethodException;
use Omega\Event\Exceptions\ServiceMethodNotFoundException;
use Omega\Event\Exceptions\ServiceNotRegisteredException;
use Omega\Event\LazyServiceEventListener;
use Tests\Event\Support\InvokableService;
use Tests\Event\Support\MethodService;
use Tests\Event\Support\PrivateMethodService;

covers(LazyServiceEventListener::class);
covers(InvalidServiceListenerException::class);
covers(InvalidServiceMethodException::class);
covers(ServiceMethodNotFoundException::class);
covers(ServiceNotRegisteredException::class);

it('rejects an empty service identifier', function (): void {
    $container = new Container();

    expect(fn () => new LazyServiceEventListener($container, ''))
        ->toThrow(InvalidServiceListenerException::class);
});

it('invokes an invokable service', function (): void {
    $container    = new Container();
    $service      = new InvokableService();
    $event        = new Event('launcher.fire');
    $container->set('invokable', $service);

    $listener = new LazyServiceEventListener($container, 'invokable');
    $listener($event);

    expect($service->getCalls())->toHaveCount(1);
    expect($service->getCalls()[0])->toBe($event);
});

it('invokes a named method on a service', function (): void {
    $container    = new Container();
    $service      = new MethodService();
    $event        = new Event('launcher.fire');
    $container->set('method-service', $service);

    $listener = new LazyServiceEventListener($container, 'method-service', 'handle');
    $listener($event);

    expect($service->getCalls())->toHaveCount(1);
    expect($service->getCalls()[0])->toBe($event);
});

it('throws when the service is not registered', function (): void {
    $container = new Container();

    $listener = new LazyServiceEventListener($container, 'missing', 'handle');

    expect(fn () => $listener(new Event('launcher.fire')))
        ->toThrow(ServiceNotRegisteredException::class);
});

it('throws when a non-callable service has no method', function (): void {
    $container = new Container();
    $container->set('plain', new \ArrayObject());

    $listener = new LazyServiceEventListener($container, 'plain');

    expect(fn () => $listener(new Event('launcher.fire')))
        ->toThrow(InvalidServiceMethodException::class);
});

it('throws when the requested method does not exist', function (): void {
    $container = new Container();
    $container->set('method-service', new MethodService());

    $listener = new LazyServiceEventListener($container, 'method-service', 'missingMethod');

    expect(fn () => $listener(new Event('launcher.fire')))
        ->toThrow(ServiceMethodNotFoundException::class);
});

it('throws when the requested method is not callable on the instance', function (): void {
    $container = new Container();
    $container->set('private-service', new PrivateMethodService());

    $listener = new LazyServiceEventListener($container, 'private-service', 'handle');

    expect(fn () => $listener(new Event('launcher.fire')))
        ->toThrow(ServiceMethodNotFoundException::class);
});