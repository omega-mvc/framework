<?php

declare(strict_types=1);

namespace Tests\Collection;

use Omega\Collection\CollectionImmutable;
use Omega\Collection\Exceptions\ImmutableCollectionException;

use function array_keys;
use function array_values;
use function count;
use function in_array;
use function json_encode;
use function str_contains;

use const JSON_THROW_ON_ERROR;

covers(CollectionImmutable::class);
covers(ImmutableCollectionException::class);

it('can work properly as an immutable collection', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test = new CollectionImmutable($original);

    expect($test->buah_1)->toEqual('mangga');
    expect($test->get('buah_1'))->toEqual('mangga');
    expect($test->has('buah_1'))->toBeTrue();
    expect($test->contain('mangga'))->toBeTrue();
    expect($test->count())->toEqual(6);

    $countIf = $test->countIf(function ($item) {
        return str_contains($item, 'e');
    });
    expect($countIf)->toEqual(4);

    expect($test->first('bukan buah'))->toEqual('mangga');
    expect($test->last('bukan buah'))->toEqual('peer');

    $keys  = array_keys($original);
    $items = array_values($original);
    expect($test->keys())->toEqual($keys);
    expect($test->items())->toEqual($items);

    $test->each(function (string $item, string $key = '') use ($original) {
        expect($original)->toContain($item);
        expect($original)->toHaveKey($key);
    });

    $some = $test->some(function ($item) {
        return str_contains($item, 'e');
    });
    expect($some)->toBeTrue();

    $every = $test->every(function ($item) {
        return !str_contains($item, 'x');
    });
    expect($every)->toBeTrue();

    $json = json_encode($original, JSON_THROW_ON_ERROR);
    expect($test->json())->toBe($json);
});

it('can act like array', function (): void {
    $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

    expect(isset($coll['one']))->toBeTrue();
    expect(isset($coll['two']))->toBeTrue();
    expect(isset($coll['three']))->toBeTrue();
});

it('can do like array', function (): void {
    $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
    $coll = new CollectionImmutable($arr);

    foreach ($arr as $key => $value) {
        expect($coll[$key])->toEqual($value);
    }

    expect(isset($coll['one']))->toBeTrue();
});

it('can be iterator', function (): void {
    $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

    foreach ($coll as $key => $value) {
        expect($coll[$key])->toEqual($value);
    }
});

it('will throw exception when setting via array access', function (): void {
    $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

    expect(function () use ($coll): void {
        $coll['one'] = 4;
    })->toThrow(ImmutableCollectionException::class);
});

it('will throw exception when removing via array access', function (): void {
    $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

    expect(function () use ($coll): void {
        unset($coll['one']);
    })->toThrow(ImmutableCollectionException::class);
});

it('can be counted using the count function', function (): void {
    $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

    expect($coll)->toHaveCount(3);
    expect(count($coll))->toEqual(3);
});

it('can randomize items in collection', function (): void {
    $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
    $coll = new CollectionImmutable($arr);
    $item = $coll->rand();

    expect(in_array($item, array_values($arr)))->toBeTrue();
});

it('can get current next prev', function (): void {
    $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

    expect($coll->current())->toEqual(1);
    expect($coll->next())->toEqual(2);
    expect($coll->prev())->toEqual(1);
});

it('can filter using strict type', function (): void {
    $coll = new CollectionImmutable(['one' => 1, 'two' => '2', 'three' => 3]);

    expect($coll->contain(1))->toBeTrue();
    expect($coll->contain('1', true))->toBeFalse();
});

it('can get first key', function (): void {
    $coll = new CollectionImmutable(['one' => 1, 'two' => '2', 'three' => 3]);

    expect($coll->firstKey())->toEqual('one');
});

it('can get first key null', function (): void {
    $coll = new CollectionImmutable([]);

    expect($coll->firstKey())->toBeNull();
});

it('can get last key', function (): void {
    $coll = new CollectionImmutable(['one' => 1, 'two' => '2', 'three' => 3]);

    expect($coll->lastKey())->toEqual('three');
});

it('can get last key null', function (): void {
    $coll = new CollectionImmutable([]);

    expect($coll->lastKey())->toBeNull();
});

it('can get firsts', function (): void {
    $coll = new CollectionImmutable([10, 20, 30, 40, 50, 60, 70, 80, 90]);

    expect($coll->firsts(2))->toEqual([10, 20]);
});

it('can get lasts', function (): void {
    $coll = new CollectionImmutable([10, 20, 30, 40, 50, 60, 70, 80, 90]);

    expect($coll->lasts(2))->toEqual([80, 90]);
});

it('can get highest', function (): void {
    $coll = new CollectionImmutable([10, 20, 30, 40, 50, 60, 70, 80, 90]);

    expect($coll->max())->toEqual(90);

    $coll = new CollectionImmutable([
        ['rank' => 10],
        ['rank' => 50],
        ['rank' => 90],
    ]);

    expect($coll->max('rank'))->toEqual(90);
});

it('can get lowest value', function (): void {
    $coll = new CollectionImmutable([10, 20, 30, 40, 50, 60, 70, 80, 90]);

    expect($coll->min())->toEqual(10);

    $coll = new CollectionImmutable([
        ['rank' => 10],
        ['rank' => 50],
        ['rank' => 90],
    ]);

    expect($coll->min('rank'))->toEqual(10);
});

it('can pluck', function (): void {
    $coll = [
        ['user' => 'taylor'],
        ['user' => 'nuno'],
        ['user' => 'giovannini'],
    ];
    $coll = new CollectionImmutable($coll);

    expect($coll->pluck('user'))->toEqual(['taylor', 'nuno', 'giovannini']);
});

it('can pluck key', function (): void {
    $coll = [
        ['id' => 1, 'user' => 'taylor'],
        ['id' => 2, 'user' => 'nuno'],
        ['id' => 3, 'user' => 'giovannini'],
    ];
    $coll = new CollectionImmutable($coll);

    expect($coll->pluck('user', 'id'))->toEqual([1 => 'taylor', 2 => 'nuno', 3 => 'giovannini']);
});
