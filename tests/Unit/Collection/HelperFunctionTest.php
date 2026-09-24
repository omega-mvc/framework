<?php

declare(strict_types=1);

namespace Tests\Collection;

use Omega\Collection\Collection;
use Omega\Collection\CollectionImmutable;

use function Omega\Collection\collection;
use function Omega\Collection\collection_immutable;

covers(Collection::class);
covers('Omega\Collection\collection_immutable');

it('creates a Collection instance with the collection helper', function (): void {
    expect(collection()::class)->toBe(Collection::class);
})->coversFunction('Omega\Collection\collection');

it('collection helper wraps the given array', function (): void {
    expect(collection(['a' => 1])->all())->toBe(['a' => 1]);
})->coversFunction('Omega\Collection\collection');

it('collection helper is chainable', function (): void {
    expect(collection([1, 2, 3])->map(static fn (int $item): int => $item * 2)->all())->toBe([2, 4, 6]);
})->coversFunction('Omega\Collection\collection');

it('creates an immutable Collection instance with the collection_immutable helper', function (): void {
    expect(collection_immutable()::class)->toBe(CollectionImmutable::class);
});

it('collection_immutable helper wraps the given array', function (): void {
    expect(collection_immutable([1, 2])->all())->toBe([1, 2]);
});

it('collection_immutable helper is fluent', function (): void {
    $collection = collection_immutable([1, 2, 3]);
    $doubled    = [];

    $result = $collection->each(static function (int $item) use (&$doubled): void {
        $doubled[] = $item * 2;
    });

    expect($result)->toBe($collection);
    expect($doubled)->toBe([2, 4, 6]);
});