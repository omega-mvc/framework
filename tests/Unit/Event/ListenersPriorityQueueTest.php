<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\EventInterface;
use Omega\Event\ListenersPriorityQueue;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function count;
use function iterator_to_array;

#[CoversClass(ListenersPriorityQueue::class)]
final class ListenersPriorityQueueTest extends TestCase
{
    public function testIsEmptyByDefault(): void
    {
        $queue = new ListenersPriorityQueue();

        $this->assertEmpty($queue->getAll());
        $this->assertNull($queue->getPriority(static function (EventInterface $event): void {
        }));
    }

    public function testReturnsListenersInDescendingPriorityOrder(): void
    {
        $queue = new ListenersPriorityQueue();
        $low   = static function (EventInterface $event): void {
        };
        $mid   = static function (EventInterface $event): void {
        };
        $high  = static function (EventInterface $event): void {
        };

        $queue->add($low, -5);
        $queue->add($high, 5);
        $queue->add($mid, 0);

        $this->assertEquals([$high, $mid, $low], $queue->getAll());
    }

    public function testPreservesInsertionOrderWithinTheSamePriority(): void
    {
        $queue = new ListenersPriorityQueue();
        $first  = static function (EventInterface $event): void {
        };
        $second = static function (EventInterface $event): void {
        };

        $queue->add($first, 0);
        $queue->add($second, 0);

        $this->assertEquals([$first, $second], $queue->getAll());
    }

    public function testReportsThePriorityAssignedToAListener(): void
    {
        $queue    = new ListenersPriorityQueue();
        $callback = static function (EventInterface $event): void {
        };

        $queue->add($callback, 10);

        $this->assertSame(10, $queue->getPriority($callback));
    }

    public function testReturnsTheDefaultWhenTheListenerIsNotRegistered(): void
    {
        $queue      = new ListenersPriorityQueue();
        $registered = static function (EventInterface $event): void {
        };
        $unknown    = static function (EventInterface $event): void {
        };

        $queue->add($registered, 0);

        $this->assertNull($queue->getPriority($unknown));
    }

    public function testChecksWhetherAListenerIsRegistered(): void
    {
        $queue      = new ListenersPriorityQueue();
        $registered = static function (EventInterface $event): void {
        };
        $unknown    = static function (EventInterface $event): void {
        };

        $queue->add($registered, 0);

        $this->assertTrue($queue->has($registered));
        $this->assertFalse($queue->has($unknown));
    }

    public function testRemovesARegisteredListener(): void
    {
        $queue = new ListenersPriorityQueue();
        $first  = static function (EventInterface $event): void {
        };
        $second = static function (EventInterface $event): void {
        };

        $queue->add($first, 0);
        $queue->add($second, 0);
        $queue->remove($first);

        $this->assertFalse($queue->has($first));
        $this->assertTrue($queue->has($second));
        $this->assertEquals([$second], $queue->getAll());
    }

    public function testRemovesAListenerFoundInALowerPriorityBucket(): void
    {
        $queue  = new ListenersPriorityQueue();
        $first  = static function (EventInterface $event): void {
        };
        $second = static function (EventInterface $event): void {
        };

        $queue->add($first, 5);
        $queue->add($second, 0);
        $queue->remove($second);

        $this->assertFalse($queue->has($second));
        $this->assertTrue($queue->has($first));
        $this->assertEquals([$first], $queue->getAll());
    }

    public function testCountsAllRegisteredListeners(): void
    {
        $queue = new ListenersPriorityQueue();

        $queue->add(static function (EventInterface $event): void {
        }, 0);
        $queue->add(static function (EventInterface $event): void {
        }, 0);
        $queue->add(static function (EventInterface $event): void {
        }, 1);

        $this->assertSame(3, count($queue));
    }

    public function testIteratesOverListenersInExecutionOrder(): void
    {
        $queue = new ListenersPriorityQueue();
        $low   = static function (EventInterface $event): void {
        };
        $high  = static function (EventInterface $event): void {
        };

        $queue->add($low, -1);
        $queue->add($high, 2);

        $this->assertEquals([$high, $low], iterator_to_array($queue->getIterator()));
    }
}
