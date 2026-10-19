<?php

declare(strict_types=1);

namespace Tests\Event;

use ArrayObject;
use Omega\Container\Container;
use Omega\Event\Event;
use Omega\Event\Exceptions\InvalidServiceListenerException;
use Omega\Event\Exceptions\InvalidServiceMethodException;
use Omega\Event\Exceptions\ServiceMethodNotFoundException;
use Omega\Event\Exceptions\ServiceNotRegisteredException;
use Omega\Event\LazyServiceEventListener;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Event\Support\InvokableService;
use Tests\Event\Support\MethodService;
use Tests\Event\Support\PrivateMethodService;
use Tests\TestCase;

#[CoversClass(LazyServiceEventListener::class)]
#[CoversClass(InvalidServiceListenerException::class)]
#[CoversClass(InvalidServiceMethodException::class)]
#[CoversClass(ServiceMethodNotFoundException::class)]
#[CoversClass(ServiceNotRegisteredException::class)]
final class LazyServiceEventListenerTest extends TestCase
{
    public function testRejectsAnEmptyServiceIdentifier(): void
    {
        $container = new Container();

        $this->expectException(InvalidServiceListenerException::class);

        new LazyServiceEventListener($container, '');
    }

    public function testInvokesAnInvokableService(): void
    {
        $container = new Container();
        $service   = new InvokableService();
        $event     = new Event('launcher.fire');
        $container->set('invokable', $service);

        $listener = new LazyServiceEventListener($container, 'invokable');
        $listener($event);

        $this->assertCount(1, $service->getCalls());
        $this->assertSame($event, $service->getCalls()[0]);
    }

    public function testInvokesANamedMethodOnAService(): void
    {
        $container = new Container();
        $service   = new MethodService();
        $event     = new Event('launcher.fire');
        $container->set('method-service', $service);

        $listener = new LazyServiceEventListener($container, 'method-service', 'handle');
        $listener($event);

        $this->assertCount(1, $service->getCalls());
        $this->assertSame($event, $service->getCalls()[0]);
    }

    public function testThrowsWhenTheServiceIsNotRegistered(): void
    {
        $container = new Container();

        $listener = new LazyServiceEventListener($container, 'missing', 'handle');

        $this->expectException(ServiceNotRegisteredException::class);

        $listener(new Event('launcher.fire'));
    }

    public function testThrowsWhenANonCallableServiceHasNoMethod(): void
    {
        $container = new Container();
        $container->set('plain', new ArrayObject());

        $listener = new LazyServiceEventListener($container, 'plain');

        $this->expectException(InvalidServiceMethodException::class);

        $listener(new Event('launcher.fire'));
    }

    public function testThrowsWhenTheRequestedMethodDoesNotExist(): void
    {
        $container = new Container();
        $container->set('method-service', new MethodService());

        $listener = new LazyServiceEventListener($container, 'method-service', 'missingMethod');

        $this->expectException(ServiceMethodNotFoundException::class);

        $listener(new Event('launcher.fire'));
    }

    public function testThrowsWhenTheRequestedMethodIsNotCallableOnTheInstance(): void
    {
        $container = new Container();
        $container->set('private-service', new PrivateMethodService());

        $listener = new LazyServiceEventListener($container, 'private-service', 'handle');

        $this->expectException(ServiceMethodNotFoundException::class);

        $listener(new Event('launcher.fire'));
    }

    public function testThrowsWhenANonObjectServiceExposesAMethod(): void
    {
        $container = new Container();
        $container->set('scalar', 'not-a-callable');

        $listener = new LazyServiceEventListener($container, 'scalar', 'handle');

        $this->expectException(InvalidServiceMethodException::class);

        $listener(new Event('launcher.fire'));
    }
}
