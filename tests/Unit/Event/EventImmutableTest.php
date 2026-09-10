<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\EventImmutable;
use Omega\Event\Exceptions\EventImmutableException;

covers(EventImmutable::class);
covers(EventImmutableException::class);

it('creates an immutable event with arguments', function (): void {
    $event = new EventImmutable('route.before', ['uri' => '/home', 'method' => 'GET']);

    expect($event->getName())->toBe('route.before');
    expect($event->getArguments())->toEqual(['uri' => '/home', 'method' => 'GET']);
    expect($event->getArgument('uri'))->toBe('/home');
    expect($event->hasArgument('method'))->toBeTrue();
});

it('rejects setting an argument', function (): void {
    $event = new EventImmutable('route.before', ['uri' => '/home']);

    expect(fn () => $event['uri'] = '/admin')->toThrow(EventImmutableException::class);
});

it('rejects removing an argument', function (): void {
    $event = new EventImmutable('route.before', ['uri' => '/home']);

    expect(function () use ($event): void {
        unset($event['uri']);
    })->toThrow(EventImmutableException::class);
});