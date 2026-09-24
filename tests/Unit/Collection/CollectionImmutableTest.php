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

it('can pluck from objects', function (): void {
    $item1 = new class {
        public string $user = 'taylor';
        public int $id     = 1;
    };
    $item2 = new class {
        public string $user = 'nuno';
        public int $id     = 2;
    };

    $coll = new CollectionImmutable([$item1, $item2]);

    expect($coll->pluck('user'))->toEqual(['taylor', 'nuno']);
    expect($coll->pluck('user', 'id'))->toEqual([1 => 'taylor', 2 => 'nuno']);
});

it('skips non array or object items when plucking', function (): void {
    $coll = new CollectionImmutable(['nope', 42, ['user' => 'taylor']]);

    expect($coll->pluck('user'))->toEqual(['taylor']);
});

it('can count by value', function (): void {
    $coll = new CollectionImmutable(['mangga', 'mangga', 'jeruk', 'apel']);

    expect($coll->countBy())->toEqual(['mangga' => 2, 'jeruk' => 1, 'apel' => 1]);
});

it('can count by float and object values', function (): void {
    $value = new class {
        public function __toString(): string
        {
            return 'x';
        }
    };

    $coll = new CollectionImmutable([1.5, 1.5, 2.5, $value, $value, ['irrelevant']]);

    expect($coll->countBy())->toEqual(['1.5' => 2, '2.5' => 1, 'x' => 2]);
});

it('stops iterating when callback returns false', function (): void {
    $visited = [];
    $coll    = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

    $coll->each(function (int $item, string $key) use (&$visited): bool {
        $visited[] = $key;

        return $item < 2;
    });

    expect($visited)->toEqual(['one', 'two']);
});

it('returns false when no item satisfies the condition', function (): void {
    $coll = new CollectionImmutable([1, 3, 5]);

    expect($coll->some(static fn (int $item): bool => $item % 2 === 0))->toBeFalse();
});

it('returns false when an item fails the condition', function (): void {
    $coll = new CollectionImmutable([2, 4, 5]);

    expect($coll->every(static fn (int $item): bool => $item % 2 === 0))->toBeFalse();
});

it('can calculate the sum', function (): void {
    $coll = new CollectionImmutable([10, 20, 30]);

    expect($coll->sum())->toEqual(60);
});

it('can calculate the average', function (): void {
    $coll = new CollectionImmutable([10, 20, 30]);

    expect($coll->avg())->toEqual(20);
});

it('returns zero when computing max on an empty collection', function (): void {
    expect((new CollectionImmutable([]))->max())->toEqual(0);
});

it('returns zero when computing min on an empty collection', function (): void {
    expect((new CollectionImmutable([]))->min())->toEqual(0);
});

it('counts items matching a strictly true condition', function (): void {
    $coll = new CollectionImmutable([1, 2, 3, 4]);

    expect($coll->countIf(static fn (int $item): bool => $item % 2 === 0))->toBe(2);
});

it('returns zero when countIf finds no match', function (): void {
    $coll = new CollectionImmutable([1, 3, 5]);

    expect($coll->countIf(static fn (int $item): bool => $item % 2 === 0))->toBe(0);
});

it('returns zero when countIf runs over an empty collection', function (): void {
    expect((new CollectionImmutable([]))->countIf(static fn (int $item): bool => true))->toBe(0);
});

it('only counts condition results strictly equal to true', function (): void {
    $coll = new CollectionImmutable([1, 2, 3]);

    expect($coll->countIf(static fn (int $item): int => $item % 2))->toBe(0);
});

it('can count by integer values', function (): void {
    $coll = new CollectionImmutable([1, 1, 2]);

    expect($coll->countBy())->toEqual([1 => 2, 2 => 1]);
});

it('ignores non stringable values when counting by', function (): void {
    $coll = new CollectionImmutable([null, true, ['x']]);

    expect($coll->countBy())->toEqual([]);
});

it('can count by over an empty collection', function (): void {
    expect((new CollectionImmutable([]))->countBy())->toEqual([]);
});

it('can run each over an empty collection', function (): void {
    $coll = new CollectionImmutable([]);

    expect($coll->each(static fn (int $item): bool => true))->toBe($coll);
});

it('returns true when an item satisfies the condition', function (): void {
    $coll = new CollectionImmutable([1, 2, 3]);

    expect($coll->some(static fn (int $item): bool => $item === 2))->toBeTrue();
});

it('returns false when some runs over an empty collection', function (): void {
    expect((new CollectionImmutable([]))->some(static fn (int $item): bool => true))->toBeFalse();
});

it('returns true when every item satisfies the condition', function (): void {
    $coll = new CollectionImmutable([2, 4, 6]);

    expect($coll->every(static fn (int $item): bool => $item % 2 === 0))->toBeTrue();
});

it('returns true when every runs over an empty collection', function (): void {
    expect((new CollectionImmutable([]))->every(static fn (int $item): bool => false))->toBeTrue();
});

it('returns zero when max receives no numeric values', function (): void {
    expect((new CollectionImmutable(['a', 'b']))->max())->toBe(0);
});

it('returns zero when min receives no numeric values', function (): void {
    expect((new CollectionImmutable(['a', 'b']))->min())->toBe(0);
});

it('returns zero when the max key is absent', function (): void {
    $coll = new CollectionImmutable([['a' => 1], ['a' => 2]]);

    expect($coll->max('missing'))->toBe(0);
});

it('returns zero when the min key is absent', function (): void {
    $coll = new CollectionImmutable([['a' => 1], ['a' => 2]]);

    expect($coll->min('missing'))->toBe(0);
});

it('can find the max among negative values', function (): void {
    expect((new CollectionImmutable([-5, -2]))->max())->toBe(-2);
});

it('can find the min among a single item', function (): void {
    expect((new CollectionImmutable([7]))->min())->toBe(7);
});

it('can pluck over an empty collection', function (): void {
    expect((new CollectionImmutable([]))->pluck('user'))->toEqual([]);
});

it('can pluck with a key when some items lack it', function (): void {
    $coll = new CollectionImmutable([['id' => 1, 'user' => 'taylor'], ['user' => 'nuno']]);

    expect($coll->pluck('user', 'id'))->toEqual([1 => 'taylor']);
});
