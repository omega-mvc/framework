<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\AbstractEvent;
use Omega\Event\Event;
use Omega\Event\Exceptions\InvalidEventArgumentNameException;

use function count;
use function serialize;
use function unserialize;

covers(AbstractEvent::class);
covers(Event::class);

it('returns the event name', function (): void {
    $event = new Event('order.created');

    expect($event->getName())->toBe('order.created');
});

it('returns an argument with its value', function (): void {
    $event = new Event('order.created', ['orderId' => 42]);

    expect($event->getArgument('orderId'))->toBe(42);
});

it('returns the default when an argument is missing', function (): void {
    $event = new Event('order.created');

    expect($event->getArgument('orderId', 0))->toBe(0);
    expect($event->getArgument('orderId'))->toBeNull();
});

it('checks whether an argument exists', function (): void {
    $event = new Event('order.created', ['orderId' => 42]);

    expect($event->hasArgument('orderId'))->toBeTrue();
    expect($event->hasArgument('missing'))->toBeFalse();
});

it('returns all arguments', function (): void {
    $event = new Event('order.created', ['orderId' => 42]);

    expect($event->getArguments())->toEqual(['orderId' => 42]);
});

it('keeps an existing argument when adding again', function (): void {
    $event = (new Event('order.created'))->addArgument('orderId', 1);

    $event->addArgument('orderId', 2);

    expect($event->getArgument('orderId'))->toBe(1);
});

it('overrides an argument when setting again', function (): void {
    $event = (new Event('order.created'))->setArgument('orderId', 1);

    $event->setArgument('orderId', 2);

    expect($event->getArgument('orderId'))->toBe(2);
});

it('removes an argument returning its previous value', function (): void {
    $event = new Event('order.created', ['orderId' => 42, 'keep' => true]);

    expect($event->removeArgument('orderId'))->toBe(42);
    expect($event->hasArgument('orderId'))->toBeFalse();
    expect($event->removeArgument('orderId'))->toBeNull();
    expect($event->getArgument('keep'))->toBeTrue();
});

it('clears all arguments returning the previous set', function (): void {
    $event = new Event('order.created', ['orderId' => 42]);

    expect($event->clearArguments())->toEqual(['orderId' => 42]);
    expect($event->getArguments())->toBeEmpty();
});

it('counts its arguments', function (): void {
    $event = new Event('order.created', ['orderId' => 42, 'customer' => 'morpheus']);

    expect(count($event))->toBe(2);
});

it('exposes arguments through array access', function (): void {
    $event = new Event('order.created');

    $event['orderId'] = 42;

    expect(isset($event['orderId']))->toBeTrue();
    expect($event['orderId'])->toBe(42);

    unset($event['orderId']);

    expect(isset($event['orderId']))->toBeFalse();
    expect($event['orderId'])->toBeNull();
});

it('rejects a null argument name in array access', function (): void {
    $event = new Event('order.created');

    expect(fn () => $event->offsetSet(null, 42))->toThrow(InvalidEventArgumentNameException::class);
});

it('rejects an integer argument name in array access', function (): void {
    $event = new Event('order.created');

    expect(fn () => $event->offsetSet(1, 42))->toThrow(InvalidEventArgumentNameException::class);
});

it('returns null when reading an integer offset', function (): void {
    $event = new Event('order.created', ['orderId' => 42]);

    expect($event->offsetGet(0))->toBeNull();
    expect($event->offsetExists(0))->toBeFalse();
});

it('controls propagation', function (): void {
    $event = new Event('order.created');

    expect($event->isStopped())->toBeFalse();

    $event->stopPropagation();

    expect($event->isStopped())->toBeTrue();
});

it('survives a serialization roundtrip', function (): void {
    $event    = new Event('order.created', ['orderId' => 42]);
    $restored = unserialize(serialize($event));

    expect($restored)->toBeInstanceOf(Event::class);

    if (!$restored instanceof Event) {
        throw new \UnexpectedValueException('Failed to unserialize the event.');
    }

    expect($restored->getName())->toBe('order.created');
    expect($restored->getArguments())->toEqual(['orderId' => 42]);
    expect($restored->isStopped())->toBeFalse();
});

it('restores the stopped flag after serialization', function (): void {
    $event = new Event('order.created');
    $event->stopPropagation();

    $restored = unserialize(serialize($event));

    expect($restored)->toBeInstanceOf(Event::class);

    if (!$restored instanceof Event) {
        throw new \UnexpectedValueException('Failed to unserialize the event.');
    }

    expect($restored->isStopped())->toBeTrue();
});

it('supports the legacy serialize roundtrip', function (): void {
    $event    = new Event('order.created', ['orderId' => 42]);
    $restored = new Event('temporary');
    $restored->unserialize($event->serialize());

    expect($restored->getName())->toBe('order.created');
    expect($restored->getArguments())->toEqual(['orderId' => 42]);
});

it('rejects a non-array payload', function (): void {
    $event = new Event('temporary');

    expect(fn () => $event->unserialize(serialize(42)))->toThrow(\UnexpectedValueException::class);
});

it('rejects a malformed serialized event', function (): void {
    $event = new Event('temporary');

    expect(fn () => $event->unserialize(serialize(['name' => 5, 'arguments' => [], 'stopped' => false])))
        ->toThrow(\UnexpectedValueException::class);
});