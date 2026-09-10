<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\EventInterface;
use Omega\Event\ListenersPriorityQueue;

use function iterator_to_array;

covers(ListenersPriorityQueue::class);

it('is empty by default', function (): void {
    $queue = new ListenersPriorityQueue();

    expect($queue->getAll())->toBeEmpty();
    expect($queue->getPriority(static function (EventInterface $event): void {
    }))->toBeNull();
});

it('returns listeners in descending priority order', function (): void {
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

    expect($queue->getAll())->toEqual([$high, $mid, $low]);
});

it('preserves insertion order within the same priority', function (): void {
    $queue = new ListenersPriorityQueue();
    $first  = static function (EventInterface $event): void {
    };
    $second = static function (EventInterface $event): void {
    };

    $queue->add($first, 0);
    $queue->add($second, 0);

    expect($queue->getAll())->toEqual([$first, $second]);
});

it('reports the priority assigned to a listener', function (): void {
    $queue = new ListenersPriorityQueue();
    $callback = static function (EventInterface $event): void {
    };

    $queue->add($callback, 10);

    expect($queue->getPriority($callback))->toBe(10);
});

it('returns the default when the listener is not registered', function (): void {
    $queue = new ListenersPriorityQueue();
    $registered = static function (EventInterface $event): void {
    };
    $unknown = static function (EventInterface $event): void {
    };

    $queue->add($registered, 0);

    expect($queue->getPriority($unknown))->toBeNull();
});

it('checks whether a listener is registered', function (): void {
    $queue = new ListenersPriorityQueue();
    $registered = static function (EventInterface $event): void {
    };
    $unknown = static function (EventInterface $event): void {
    };

    $queue->add($registered, 0);

    expect($queue->has($registered))->toBeTrue();
    expect($queue->has($unknown))->toBeFalse();
});

it('removes a registered listener', function (): void {
    $queue = new ListenersPriorityQueue();
    $first  = static function (EventInterface $event): void {
    };
    $second = static function (EventInterface $event): void {
    };

    $queue->add($first, 0);
    $queue->add($second, 0);
    $queue->remove($first);

    expect($queue->has($first))->toBeFalse();
    expect($queue->has($second))->toBeTrue();
    expect($queue->getAll())->toEqual([$second]);
});

it('counts all registered listeners', function (): void {
    $queue = new ListenersPriorityQueue();

    $queue->add(static function (EventInterface $event): void {
    }, 0);
    $queue->add(static function (EventInterface $event): void {
    }, 0);
    $queue->add(static function (EventInterface $event): void {
    }, 1);

    expect(count($queue))->toBe(3);
});

it('iterates over listeners in execution order', function (): void {
    $queue = new ListenersPriorityQueue();
    $low   = static function (EventInterface $event): void {
    };
    $high  = static function (EventInterface $event): void {
    };

    $queue->add($low, -1);
    $queue->add($high, 2);

    expect(iterator_to_array($queue->getIterator()))->toEqual([$high, $low]);
});