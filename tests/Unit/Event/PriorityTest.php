<?php

declare(strict_types=1);

namespace Tests\Event;

use Omega\Event\Priority;

use function array_map;

covers(Priority::class);

it('exposes the minimum priority', function (): void {
    expect(Priority::MIN->value)->toBe(-3);
});

it('exposes a low priority', function (): void {
    expect(Priority::LOW->value)->toBe(-2);
});

it('exposes a below normal priority', function (): void {
    expect(Priority::BELOW_NORMAL->value)->toBe(-1);
});

it('exposes the normal priority', function (): void {
    expect(Priority::NORMAL->value)->toBe(0);
});

it('exposes an above normal priority', function (): void {
    expect(Priority::ABOVE_NORMAL->value)->toBe(1);
});

it('exposes a high priority', function (): void {
    expect(Priority::HIGH->value)->toBe(2);
});

it('exposes the maximum priority', function (): void {
    expect(Priority::MAX->value)->toBe(3);
});

it('orders cases in ascending priority', function (): void {
    $values = array_map(static fn (Priority $priority): int => $priority->value, Priority::cases());

    expect($values)->toEqual([-3, -2, -1, 0, 1, 2, 3]);
});