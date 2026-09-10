<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\Dispatcher\Dispatcher;
use Omega\Event\Event;
use Omega\Event\EventInterface;
use Omega\Event\Priority;
use Tests\Event\Support\TestSubscriber;

covers(Dispatcher::class);

it('dispatches an event to matching listeners', function (): void {
    $dispatcher = new Dispatcher();
    $called     = false;

    $dispatcher->addListener('order.created', function (EventInterface $event) use (&$called): void {
        $called = $event->getName() === 'order.created';
    });

    $dispatcher->dispatch(new Event('order.created'));

    expect($called)->toBeTrue();
});

it('does not call listeners of other events', function (): void {
    $dispatcher = new Dispatcher();
    $called     = false;

    $dispatcher->addListener('order.created', function () use (&$called): void {
        $called = true;
    });

    $dispatcher->dispatch(new Event('customer.created'));

    expect($called)->toBeFalse();
});

it('executes listeners in descending priority order', function (): void {
    $dispatcher = new Dispatcher();
    $order      = [];

    $dispatcher->addListener('order.created', function () use (&$order): void {
        $order[] = 'low';
    }, -5);
    $dispatcher->addListener('order.created', function () use (&$order): void {
        $order[] = 'high';
    }, 5);
    $dispatcher->addListener('order.created', function () use (&$order): void {
        $order[] = 'mid';
    }, 0);

    $dispatcher->dispatch(new Event('order.created'));

    expect($order)->toEqual(['high', 'mid', 'low']);
});

it('stops propagation when a listener stops the event', function (): void {
    $dispatcher = new Dispatcher();
    $stopped    = false;

    $dispatcher->addListener('order.created', function (EventInterface $event) use (&$stopped): void {
        $stopped = true;
        $event->stopPropagation();
    });
    $dispatcher->addListener('order.created', function () use (&$stopped): void {
        $stopped = false;
    });

    $dispatcher->dispatch(new Event('order.created'));

    expect($stopped)->toBeTrue();
});

it('returns the dispatched event instance', function (): void {
    $dispatcher = new Dispatcher();
    $event      = new Event('order.created');

    expect($dispatcher->dispatch($event))->toBe($event);
});

it('does nothing when dispatching an event without listeners', function (): void {
    $dispatcher = new Dispatcher();
    $event      = new Event('order.created');

    expect($dispatcher->dispatch($event))->toBe($event);
});

it('reports the priority of a registered listener', function (): void {
    $dispatcher = new Dispatcher();
    $listener   = function (EventInterface $event): void {
    };

    $dispatcher->addListener('order.created', $listener, 7);

    expect($dispatcher->getListenerPriority('order.created', $listener))->toBe(7);
    expect($dispatcher->getListenerPriority('unknown.event', $listener))->toBeNull();
});

it('registers all subscriber events', function (): void {
    $dispatcher = new Dispatcher();
    $subscriber = new TestSubscriber();

    $dispatcher->addSubscriber($subscriber);

    expect($dispatcher->countListeners('subscriber.alpha'))->toBe(1);
    expect($dispatcher->countListeners('subscriber.beta'))->toBe(1);
    expect($dispatcher->countListeners('subscriber.gamma'))->toBe(1);
    expect($dispatcher->countListeners('subscriber.stop'))->toBe(1);
});

it('honors subscriber priorities', function (): void {
    $dispatcher = new Dispatcher();
    $subscriber = new TestSubscriber();

    $dispatcher->addSubscriber($subscriber);

    expect($dispatcher->getListenerPriority('subscriber.beta', [$subscriber, 'onBeta']))
        ->toBe(Priority::HIGH->value);
    expect($dispatcher->getListenerPriority('subscriber.gamma', [$subscriber, 'onGamma']))
        ->toBe(-1);
});

it('dispatches subscriber listeners', function (): void {
    $dispatcher = new Dispatcher();
    $subscriber = new TestSubscriber();

    $dispatcher->addSubscriber($subscriber);
    $dispatcher->dispatch(new Event('subscriber.alpha'));
    $dispatcher->dispatch(new Event('subscriber.beta'));

    expect($subscriber->getCalls())->toEqual(['alpha', 'beta']);
});

it('lets a subscriber stop propagation', function (): void {
    $dispatcher        = new Dispatcher();
    $subscriber        = new TestSubscriber();
    $afterSubscriber   = false;

    $dispatcher->addSubscriber($subscriber);
    $dispatcher->addListener('subscriber.stop', function () use (&$afterSubscriber): void {
        $afterSubscriber = true;
    });

    $dispatcher->dispatch(new Event('subscriber.stop'));

    expect($subscriber->getCalls())->toEqual(['stop']);
    expect($afterSubscriber)->toBeFalse();
});

it('removes all subscriber listeners', function (): void {
    $dispatcher = new Dispatcher();
    $subscriber = new TestSubscriber();

    $dispatcher->addSubscriber($subscriber);
    $dispatcher->removeSubscriber($subscriber);

    expect($dispatcher->countListeners('subscriber.alpha'))->toBe(0);
    expect($dispatcher->countListeners('subscriber.beta'))->toBe(0);
    expect($dispatcher->countListeners('subscriber.gamma'))->toBe(0);
    expect($dispatcher->countListeners('subscriber.stop'))->toBe(0);
});

it('removes a single listener', function (): void {
    $dispatcher = new Dispatcher();
    $listener   = function (EventInterface $event): void {
    };
    $other      = function (EventInterface $event): void {
    };

    $dispatcher->addListener('order.created', $listener);
    $dispatcher->addListener('order.created', $other);
    $dispatcher->removeListener('order.created', $listener);

    expect($dispatcher->hasListener($listener, 'order.created'))->toBeFalse();
    expect($dispatcher->hasListener($other, 'order.created'))->toBeTrue();
});

it('checks listener existence globally and per event', function (): void {
    $dispatcher = new Dispatcher();
    $listener   = function (EventInterface $event): void {
    };

    $dispatcher->addListener('order.created', $listener);

    expect($dispatcher->hasListener($listener))->toBeTrue();
    expect($dispatcher->hasListener($listener, 'order.created'))->toBeTrue();
    expect($dispatcher->hasListener($listener, 'other.event'))->toBeFalse();
});

it('returns listeners filtered or grouped', function (): void {
    $dispatcher = new Dispatcher();
    $first  = function (EventInterface $event): void {
    };
    $second = function (EventInterface $event): void {
    };

    $dispatcher->addListener('order.created', $first);
    $dispatcher->addListener('order.created', $second);
    $dispatcher->addListener('customer.created', $first);

    expect($dispatcher->getListeners('order.created'))->toHaveCount(2);
    expect($dispatcher->getListeners())->toHaveKeys(['order.created', 'customer.created']);
});

it('clears listeners for a single event', function (): void {
    $dispatcher = new Dispatcher();
    $listener   = function (EventInterface $event): void {
    };

    $dispatcher->addListener('order.created', $listener);
    $dispatcher->addListener('customer.created', $listener);

    $dispatcher->clearListeners('order.created');

    expect($dispatcher->countListeners('order.created'))->toBe(0);
    expect($dispatcher->countListeners('customer.created'))->toBe(1);
});

it('clears all listeners', function (): void {
    $dispatcher = new Dispatcher();
    $listener   = function (EventInterface $event): void {
    };

    $dispatcher->addListener('order.created', $listener);
    $dispatcher->addListener('customer.created', $listener);

    $dispatcher->clearListeners();

    expect($dispatcher->countListeners('order.created'))->toBe(0);
    expect($dispatcher->countListeners('customer.created'))->toBe(0);
});