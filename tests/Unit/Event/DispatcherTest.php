<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\Dispatcher\Dispatcher;
use Omega\Event\Event;
use Omega\Event\EventInterface;
use Omega\Event\Priority;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Event\Support\TestSkippingSubscriber;
use Tests\Event\Support\TestSubscriber;
use Tests\TestCase;

#[CoversClass(Dispatcher::class)]
final class DispatcherTest extends TestCase
{
    public function testDispatchesAnEventToMatchingListeners(): void
    {
        $dispatcher = new Dispatcher();
        $called     = false;

        $dispatcher->addListener('order.created', function (EventInterface $event) use (&$called): void {
            $called = $event->getName() === 'order.created';
        });

        $dispatcher->dispatch(new Event('order.created'));

        $this->assertTrue($called);
    }

    public function testDoesNotCallListenersOfOtherEvents(): void
    {
        $dispatcher = new Dispatcher();
        $called     = false;

        $dispatcher->addListener('order.created', function () use (&$called): void {
            $called = true;
        });

        $dispatcher->dispatch(new Event('customer.created'));

        $this->assertFalse($called);
    }

    public function testExecutesListenersInDescendingPriorityOrder(): void
    {
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

        $this->assertEquals(['high', 'mid', 'low'], $order);
    }

    public function testStopsPropagationWhenAListenerStopsTheEvent(): void
    {
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

        $this->assertTrue($stopped);
    }

    public function testReturnsTheDispatchedEventInstance(): void
    {
        $dispatcher = new Dispatcher();
        $event      = new Event('order.created');

        $this->assertSame($event, $dispatcher->dispatch($event));
    }

    public function testDoesNothingWhenDispatchingAnEventWithoutListeners(): void
    {
        $dispatcher = new Dispatcher();
        $event      = new Event('order.created');

        $this->assertSame($event, $dispatcher->dispatch($event));
    }

    public function testReportsThePriorityOfARegisteredListener(): void
    {
        $dispatcher = new Dispatcher();
        $listener   = function (EventInterface $event): void {
        };

        $dispatcher->addListener('order.created', $listener, 7);

        $this->assertSame(7, $dispatcher->getListenerPriority('order.created', $listener));
        $this->assertNull($dispatcher->getListenerPriority('unknown.event', $listener));
    }

    public function testRegistersAllSubscriberEvents(): void
    {
        $dispatcher = new Dispatcher();
        $subscriber = new TestSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $this->assertSame(1, $dispatcher->countListeners('subscriber.alpha'));
        $this->assertSame(1, $dispatcher->countListeners('subscriber.beta'));
        $this->assertSame(1, $dispatcher->countListeners('subscriber.gamma'));
        $this->assertSame(1, $dispatcher->countListeners('subscriber.stop'));
    }

    public function testHonorsSubscriberPriorities(): void
    {
        $dispatcher = new Dispatcher();
        $subscriber = new TestSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $this->assertSame(
            Priority::HIGH->value,
            $dispatcher->getListenerPriority('subscriber.beta', [$subscriber, 'onBeta'])
        );
        $this->assertSame(
            -1,
            $dispatcher->getListenerPriority('subscriber.gamma', [$subscriber, 'onGamma'])
        );
    }

    public function testDispatchesSubscriberListeners(): void
    {
        $dispatcher = new Dispatcher();
        $subscriber = new TestSubscriber();

        $dispatcher->addSubscriber($subscriber);
        $dispatcher->dispatch(new Event('subscriber.alpha'));
        $dispatcher->dispatch(new Event('subscriber.beta'));

        $this->assertEquals(['alpha', 'beta'], $subscriber->getCalls());
    }

    public function testLetsASubscriberStopPropagation(): void
    {
        $dispatcher      = new Dispatcher();
        $subscriber      = new TestSubscriber();
        $afterSubscriber = false;

        $dispatcher->addSubscriber($subscriber);
        $dispatcher->addListener('subscriber.stop', function () use (&$afterSubscriber): void {
            $afterSubscriber = true;
        });

        $dispatcher->dispatch(new Event('subscriber.stop'));

        $this->assertEquals(['stop'], $subscriber->getCalls());
        $this->assertFalse($afterSubscriber);
    }

    public function testRemovesAllSubscriberListeners(): void
    {
        $dispatcher = new Dispatcher();
        $subscriber = new TestSubscriber();

        $dispatcher->addSubscriber($subscriber);
        $dispatcher->removeSubscriber($subscriber);

        $this->assertSame(0, $dispatcher->countListeners('subscriber.alpha'));
        $this->assertSame(0, $dispatcher->countListeners('subscriber.beta'));
        $this->assertSame(0, $dispatcher->countListeners('subscriber.gamma'));
        $this->assertSame(0, $dispatcher->countListeners('subscriber.stop'));
    }

    public function testRemovesASingleListener(): void
    {
        $dispatcher = new Dispatcher();
        $listener   = function (EventInterface $event): void {
        };
        $other      = function (EventInterface $event): void {
        };

        $dispatcher->addListener('order.created', $listener);
        $dispatcher->addListener('order.created', $other);
        $dispatcher->removeListener('order.created', $listener);

        $this->assertFalse($dispatcher->hasListener($listener, 'order.created'));
        $this->assertTrue($dispatcher->hasListener($other, 'order.created'));
    }

    public function testChecksListenerExistenceGloballyAndPerEvent(): void
    {
        $dispatcher = new Dispatcher();
        $listener   = function (EventInterface $event): void {
        };

        $dispatcher->addListener('order.created', $listener);

        $this->assertTrue($dispatcher->hasListener($listener));
        $this->assertTrue($dispatcher->hasListener($listener, 'order.created'));
        $this->assertFalse($dispatcher->hasListener($listener, 'other.event'));
    }

    public function testSearchesListenerExistenceAcrossMultipleEvents(): void
    {
        $dispatcher = new Dispatcher();
        $first      = function (EventInterface $event): void {
        };
        $second     = function (EventInterface $event): void {
        };

        $dispatcher->addListener('order.created', $first);
        $dispatcher->addListener('customer.created', $second);

        $this->assertTrue($dispatcher->hasListener($second));
        $this->assertFalse($dispatcher->hasListener(static function (EventInterface $event): void {
        }));
    }

    public function testSkipsEmptyAndNonCallableSubscriberEvents(): void
    {
        $dispatcher = new Dispatcher();
        $subscriber = new TestSkippingSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $this->assertSame(0, $dispatcher->countListeners('subscriber.empty'));
        $this->assertSame(0, $dispatcher->countListeners('subscriber.emptyArray'));
        $this->assertSame(0, $dispatcher->countListeners('subscriber.missing'));
        $this->assertSame(0, $dispatcher->countListeners('subscriber.missingString'));
    }

    public function testReturnsListenersFilteredOrGrouped(): void
    {
        $dispatcher = new Dispatcher();
        $first      = function (EventInterface $event): void {
        };
        $second     = function (EventInterface $event): void {
        };

        $dispatcher->addListener('order.created', $first);
        $dispatcher->addListener('order.created', $second);
        $dispatcher->addListener('customer.created', $first);

        $this->assertCount(2, $dispatcher->getListeners('order.created'));

        $listeners = $dispatcher->getListeners();
        $this->assertArrayHasKey('order.created', $listeners);
        $this->assertArrayHasKey('customer.created', $listeners);
    }

    public function testClearsListenersForASingleEvent(): void
    {
        $dispatcher = new Dispatcher();
        $listener   = function (EventInterface $event): void {
        };

        $dispatcher->addListener('order.created', $listener);
        $dispatcher->addListener('customer.created', $listener);

        $dispatcher->clearListeners('order.created');

        $this->assertSame(0, $dispatcher->countListeners('order.created'));
        $this->assertSame(1, $dispatcher->countListeners('customer.created'));
    }

    public function testClearsAllListeners(): void
    {
        $dispatcher = new Dispatcher();
        $listener   = function (EventInterface $event): void {
        };

        $dispatcher->addListener('order.created', $listener);
        $dispatcher->addListener('customer.created', $listener);

        $dispatcher->clearListeners();

        $this->assertSame(0, $dispatcher->countListeners('order.created'));
        $this->assertSame(0, $dispatcher->countListeners('customer.created'));
    }

    public function testReturnsAnEmptyListForAnUnregisteredEvent(): void
    {
        $dispatcher = new Dispatcher();

        $this->assertSame([], $dispatcher->getListeners('unregistered.event'));
    }

    public function testDoesNotCallListenersWhenTheEventIsAlreadyStopped(): void
    {
        $dispatcher = new Dispatcher();
        $called     = false;

        $dispatcher->addListener('order.created', function () use (&$called): void {
            $called = true;
        });

        $event = new Event('order.created');
        $event->stopPropagation();

        $dispatcher->dispatch($event);

        $this->assertFalse($called);
    }

    public function testDoesNothingWhenRemovingAnUnregisteredListener(): void
    {
        $dispatcher = new Dispatcher();
        $listener   = static function (): void {
        };

        $dispatcher->removeListener('never.registered', $listener);

        $this->assertSame(0, $dispatcher->countListeners('never.registered'));
    }

    public function testDoesNothingWhenClearingAnUnregisteredEvent(): void
    {
        $dispatcher = new Dispatcher();

        $this->assertSame($dispatcher, $dispatcher->clearListeners('never.registered'));
        $this->assertSame(0, $dispatcher->countListeners('never.registered'));
    }
}
