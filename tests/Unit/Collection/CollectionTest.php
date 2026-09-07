<?php

declare(strict_types=1);

namespace Tests\Collection;

use Omega\Collection\Collection;
use Omega\Collection\CollectionImmutable;

use function array_filter;
use function array_keys;
use function array_map;
use function array_reverse;
use function array_values;
use function in_array;
use function json_encode;
use function ob_get_clean;
use function ob_start;
use function str_contains;
use function ucfirst;

use const JSON_THROW_ON_ERROR;

covers(Collection::class);
covers(CollectionImmutable::class);

it('can get getter setter', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test = new Collection($original);

    expect($test->buah_1)->toEqual('mangga');
    expect($test->get('buah_1'))->toEqual('mangga');

    $test->set('buah_7', 'kelengkeng');
    $test->buah_8 = 'cherry';

    expect($test->buah_8)->toEqual('cherry');
    expect($test->get('buah_7'))->toEqual('kelengkeng');

    $test->set('buah_7', 'durian');
    $test->buah_8 = 'nanas';

    expect($test->buah_8)->toEqual('nanas');
    expect($test->get('buah_7'))->toEqual('durian');
});

it('can get add remove has contain', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test = new Collection($original);

    expect($test->has('buah_1'))->toBeTrue();
    expect($test->contain('mangga'))->toBeTrue();

    $test->remove('buah_2');

    expect($test->has('buah_2'))->toBeFalse();

    $test->replace($original);
});

it('can get count and count if', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test = new Collection($original);

    expect($test->count())->toEqual(6);

    $countIf = $test->countIf(function ($item) {
        return str_contains($item, 'e');
    });

    expect($countIf)->toEqual(4);
});

it('can get first last', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test = new Collection($original);

    expect($test->first('bukan buah'))->toEqual('mangga');
    expect($test->last('bukan buah'))->toEqual('peer');
});

it('can get clear is empty replace all', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test = new Collection($original);

    expect($test->isEmpty())->toBeFalse();

    $test->clear();

    expect($test->isEmpty())->toBeTrue();

    $test->replace($original);

    expect($test->all())->toEqual($original);
});

it('can get keys items', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test  = new Collection($original);
    $keys  = array_keys($original);
    $items = array_values($original);

    expect($test->keys())->toEqual($keys);
    expect($test->items())->toEqual($items);
});

it('can get each map filter', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test = new Collection($original);

    $test->each(function (string $item, string $key = '') use ($original) {
        expect($original)->toContain($item);
        expect($original)->toHaveKey($key);
    });

    $test->map(fn ($item) => ucfirst($item));

    $copyOrigin = array_map(fn ($item) => ucfirst($item), $original);

    expect($test->all())->toEqual($copyOrigin);

    $test->replace($original);

    $test->filter(function ($item) {
        return str_contains($item, 'e');
    });

    $copyOrigin = array_filter($original, function ($item) {
        return str_contains($item, 'e');
    });

    expect($test->all())->toEqual($copyOrigin);

    $test->replace($original);
});

it('can get some every', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test = new Collection($original);

    $some = $test->some(function ($item) {
        return str_contains($item, 'e');
    });

    expect($some)->toBeTrue();

    $every = $test->every(function ($item) {
        return !str_contains($item, 'x');
    });

    expect($every)->toBeTrue();
});

it('can get json', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test = new Collection($original);
    $json = json_encode($original, JSON_THROW_ON_ERROR);

    expect($test->json())->toBe($json);
});

it('can get reverse sort', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test        = new Collection($original);
    $copyOrigin = $original;

    expect($test->reverse()->all())->toEqual(array_reverse($copyOrigin));

    $test->replace($original);

    expect($test->sort()->first())->toEqual('apel');
    expect($test->sortDesc()->first())->toEqual('rambutan');

    $test->sortBy(function ($a, $b) {
        if ($a == $b) {
            return 0;
        }

        return ($a < $b) ? -1 : 1;
    });

    expect($test->first())->toEqual('apel');

    $test->sortByDesc(function ($a, $b) {
        if ($a == $b) {
            return 0;
        }

        return ($a < $b) ? -1 : 1;
    });

    expect($test->first())->toEqual('rambutan');
    expect($test->sortKey()->first())->toEqual('mangga');
    expect($test->sortKeyDesc()->first())->toEqual('peer');

    $test->replace($original);
});

it('can get clone reject chunk split only except flatten', function (): void {
    $original = [
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
        'buah_6' => 'peer',
    ];
    $test = new Collection($original);

    expect($test->clone()->reverse()->first())->toEqual($test->last());

    $copyOrigin = $original;
    unset($copyOrigin['buah_2']);

    expect($test->reject(fn ($item) => $item == 'jeruk')->all())->toEqual($copyOrigin);

    $chunk = $test->clone()->chunk(3)->all();
    expect($chunk)->toEqual([
        ['buah_1' => 'mangga', 'buah_3' => 'apel', 'buah_4' => 'melon'],
        ['buah_5' => 'rambutan', 'buah_6' => 'peer'],
    ]);

    $split = $test->clone()->split(3)->all();
    expect($split)->toEqual([
        ['buah_1' => 'mangga', 'buah_3' => 'apel'],
        ['buah_4' => 'melon', 'buah_5' => 'rambutan'],
        ['buah_6' => 'peer'],
    ]);

    $only = $test->clone()->only(['buah_1', 'buah_5']);
    expect($only->all())->toEqual(['buah_1' => 'mangga', 'buah_5' => 'rambutan']);

    $except = $test->clone()->except(['buah_3', 'buah_4', 'buah_6']);
    expect($except->all())->toEqual(['buah_1' => 'mangga', 'buah_5' => 'rambutan']);

    $arrayNesting = [
        'first' => ['buah_1' => 'mangga', ['buah_2' => 'jeruk', 'buah_3' => 'apel', 'buah_4' => 'melon']],
        'mid'   => ['buah_4' => 'melon', ['buah_5' => 'rambutan']],
        'last'  => ['buah_6' => 'peer'],
    ];
    $flatten = new Collection($arrayNesting);

    expect($flatten->flatten()->all())->toEqual($original);
});

it('collection chain work great', function (): void {
    $origin     = [0, 1, 2, 3, 4];
    $collection = new Collection($origin);

    $chain = $collection
        ->add($origin)
        ->remove(0)
        ->set(0, 0)
        ->clear()
        ->replace($origin)
        ->each(fn ($el) => in_array($el, $origin))
        ->map(fn ($el) => $el + 100 - (2 * 50))
        ->filter(fn ($el) => $el > -1)
        ->sort()
        ->sortDesc()
        ->sortKey()
        ->sortKeyDesc()
        ->sortBy(function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        })
        ->sortByDesc(function ($a, $b) {
            if ($b == $a) {
                return 0;
            }

            return ($b < $a) ? -1 : 1;
        })
        ->all();

    expect($chain)->toEqual($origin);
});

it('can add collection from collection', function (): void {
    $arr1 = ['a' => 'b'];
    $arr2 = ['c' => 'd'];

    $collect1 = new Collection($arr1);
    $collect2 = new CollectionImmutable($arr2);

    $collect = (new Collection($arr1))->clear();
    $collect->ref($collect1)->ref($collect2);

    expect($collect->all())->toEqual(['a' => 'b', 'c' => 'd']);
});

it('can act like array', function (): void {
    $coll = new Collection(['one' => 1, 'two' => 2, 'three' => 3]);

    expect(isset($coll['one']))->toBeTrue();
    expect(isset($coll['two']))->toBeTrue();
    expect(isset($coll['three']))->toBeTrue();
});

it('can do like array', function (): void {
    $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
    $coll = new Collection($arr);

    foreach ($arr as $key => $value) {
        expect($coll[$key])->toEqual($value);
    }

    $coll['four'] = 4;

    expect(isset($coll['four']))->toBeTrue();

    unset($coll['four']);

    expect($coll->all())->toEqual($arr);
});

it('can be iterator', function (): void {
    $coll = new Collection(['one' => 1, 'two' => 2, 'three' => 3]);

    foreach ($coll as $key => $value) {
        expect($coll[$key])->toEqual($value);
    }
});

it('can be shuffled', function (): void {
    $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
    $coll = new Collection($arr);

    $coll->shuffle();

    foreach ($arr as $key => $val) {
        expect($coll->has($key))->toBeTrue();
    }
});

it('can map with keys', function (): void {
    $arr = new Collection([
        [
            'name'  => 'taylor',
            'email' => 'taylor@laravel.com',
        ], [
            'name'  => 'giovannini',
            'email' => 'giovannini@savanna.com',
        ],
    ]);

    $assocBy = $arr->assocBy(fn ($item) => [$item['name'] => $item['email']]);

    expect($assocBy->toArray())->toEqual([
        'taylor'     => 'taylor@laravel.com',
        'giovannini' => 'giovannini@savanna.com',
    ]);
});

it('can clone collection', function (): void {
    $ori = new Collection([
        'one' => 'one',
        'two' => [
            'one',
            'two' => [1, 2],
        ],
        'three' => new Collection([]),
    ]);

    $clone = clone $ori;

    $ori->set('one', 'uno');

    expect($clone->get('one'))->toEqual('one');

    $clone->set('one', 1);

    expect($ori->get('one'))->toEqual('uno');
});

it('can get sum using reduce', function (): void {
    $collection = new Collection([1, 2, 3, 4]);

    $sum = $collection->reduce(fn ($carry, $item) => $carry + $item);

    expect($sum === 10)->toBeTrue();
});

it('can get take first', function (): void {
    $coll = new Collection([10, 20, 30, 40, 50, 60, 70, 80, 90]);

    expect($coll->take(2)->toArray())->toEqual([10, 20]);
});

it('can get take last', function (): void {
    $coll = new Collection([10, 20, 30, 40, 50, 60, 70, 80, 90]);

    expect($coll->take(-2)->toArray())->toEqual([80, 90]);
});

it('can push new item', function (): void {
    $coll = new Collection([10, 20, 30, 40, 50, 60, 70, 80, 90]);
    $coll->push(100);

    expect(in_array(100, $coll->toArray()))->toBeTrue();
});

it('can get diff', function (): void {
    $coll = new Collection([1, 2, 3, 4, 5]);
    $coll->diff([2, 4, 6, 8]);

    expect($coll->items())->toEqual([1, 3, 5]);
});

it('can get diff using key', function (): void {
    $coll = new Collection([
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
    ]);
    $coll->diffKeys([
        'buah_2' => 'orange',
        'buah_4' => 'water malon',
        'buah_6' => 'six',
        'buah_8' => 'eight',
    ]);

    expect($coll->toArray())->toEqual([
        'buah_1' => 'mangga',
        'buah_3' => 'apel',
        'buah_5' => 'rambutan',
    ]);
});

it('can get diff using assoc', function (): void {
    $coll = new Collection([
        'color'   => 'green',
        'type'    => 'library',
        'version' => 0,
    ]);
    $coll->diffAssoc([
        'color'   => 'orange',
        'type'    => 'framework',
        'version' => 10,
        'used'    => 100,
    ]);

    expect($coll->toArray())->toEqual([
        'color'   => 'green',
        'type'    => 'library',
        'version' => 0,
    ]);
});

it('can get complement', function (): void {
    $coll = new Collection([1, 2, 3, 4, 5]);
    $coll->complement([2, 4, 6, 8]);

    expect($coll->items())->toEqual([6, 8]);
});

it('can get complement using key', function (): void {
    $coll = new Collection([
        'buah_1' => 'mangga',
        'buah_2' => 'jeruk',
        'buah_3' => 'apel',
        'buah_4' => 'melon',
        'buah_5' => 'rambutan',
    ]);
    $coll->complementKeys([
        'buah_2' => 'orange',
        'buah_4' => 'water malon',
        'buah_6' => 'six',
        'buah_8' => 'eight',
    ]);

    expect($coll->toArray())->toEqual([
        'buah_6' => 'six',
        'buah_8' => 'eight',
    ]);
});

it('can get complement using assoc', function (): void {
    $coll = new Collection([
        'color'   => 'green',
        'type'    => 'library',
        'version' => 0,
    ]);
    $coll->complementAssoc([
        'color'   => 'orange',
        'type'    => 'framework',
        'version' => 10,
        'used'    => 100,
    ]);

    expect($coll->toArray())->toEqual([
        'color'   => 'orange',
        'type'    => 'framework',
        'version' => 10,
        'used'    => 100,
    ]);
});

it('can get filtered using where', function (): void {
    $data = [
        ['user' => 'user1', 'age' => 10],
        ['user' => 'user2', 'age' => 12],
        ['user' => 'user3', 'age' => 10],
        ['user' => 'user4', 'age' => 13],
        ['user' => 'user5', 'age' => 14],
    ];

    $equal = new Collection($data)->where('age', '=', '13');
    expect($equal->toArray())->toEqual([
        3 => ['user' => 'user4', 'age' => 13],
    ]);

    $identical = new Collection($data)->where('age', '===', 13);
    expect($identical->toArray())->toEqual([
        3 => ['user' => 'user4', 'age' => 13],
    ]);

    $notEqual = new Collection($data)->where('age', '!=', '13');
    expect($notEqual->toArray())->toEqual([
        ['user' => 'user1', 'age' => 10],
        ['user' => 'user2', 'age' => 12],
        ['user' => 'user3', 'age' => 10],
        4       => ['user' => 'user5', 'age' => 14],
    ]);

    $notEqualIdentical = new Collection($data)->where('age', '!==', 13);
    expect($notEqualIdentical->toArray())->toEqual([
        ['user' => 'user1', 'age' => 10],
        ['user' => 'user2', 'age' => 12],
        ['user' => 'user3', 'age' => 10],
        4       => ['user' => 'user5', 'age' => 14],
    ]);

    $greaterThan = new Collection($data)->where('age', '>', 13);
    expect($greaterThan->toArray())->toEqual([
        4 => ['user' => 'user5', 'age' => 14],
    ]);

    $greaterThanEqual = new Collection($data)->where('age', '>=', 13);
    expect($greaterThanEqual->toArray())->toEqual([
        3 => ['user' => 'user4', 'age' => 13],
        4 => ['user' => 'user5', 'age' => 14],
    ]);

    $lessThan = new Collection($data)->where('age', '<', 13);
    expect($lessThan->toArray())->toEqual([
        ['user' => 'user1', 'age' => 10],
        ['user' => 'user2', 'age' => 12],
        ['user' => 'user3', 'age' => 10],
    ]);

    $lessThanEqual = new Collection($data)->where('age', '<=', 13);
    expect($lessThanEqual->toArray())->toEqual([
        ['user' => 'user1', 'age' => 10],
        ['user' => 'user2', 'age' => 12],
        ['user' => 'user3', 'age' => 10],
        ['user' => 'user4', 'age' => 13],
    ]);
});

it('can filter data using where in', function (): void {
    $data = [
        ['user' => 'user1', 'age' => 10],
        ['user' => 'user2', 'age' => 12],
        ['user' => 'user3', 'age' => 10],
        ['user' => 'user4', 'age' => 13],
        ['user' => 'user5', 'age' => 14],
    ];

    $whereIn = new Collection($data)->whereIn('age', [10, 12]);

    expect($whereIn->toArray())->toEqual([
        ['user' => 'user1', 'age' => 10],
        ['user' => 'user2', 'age' => 12],
        ['user' => 'user3', 'age' => 10],
    ]);
});

it('can filter data using where not in', function (): void {
    $data = [
        ['user' => 'user1', 'age' => 10],
        ['user' => 'user2', 'age' => 12],
        ['user' => 'user3', 'age' => 10],
        ['user' => 'user4', 'age' => 13],
        ['user' => 'user5', 'age' => 14],
    ];

    $whereNotIn = new Collection($data)->whereNotIn('age', [13, 14]);

    expect($whereNotIn->toArray())->toEqual([
        ['user' => 'user1', 'age' => 10],
        ['user' => 'user2', 'age' => 12],
        ['user' => 'user3', 'age' => 10],
    ]);
});

it('can convert to immutable', function (): void {
    $coll      = new Collection(['a' => 1, 'b' => 2]);
    $immutable = $coll->immutable();

    $this->assertInstanceOf(CollectionImmutable::class, $immutable);
    expect($immutable->all())->toEqual(['a' => 1, 'b' => 2]);
});

it('can handle empty collection', function (): void {
    $coll = (new Collection(['a' => 1]))->clear();

    expect($coll->isEmpty())->toBeTrue();
    expect($coll->first())->toBeNull();
    expect($coll->last())->toBeNull();
    expect($coll->keys())->toEqual([]);
    expect($coll->items())->toEqual([]);
});

it('can handle null key and value', function (): void {
    $coll = new Collection([null => null]);

    expect($coll->has(null))->toBeTrue();
});

it('can dump without error', function (): void {
    $coll = new Collection(['a' => 1]);

    $this->expectNotToPerformAssertions();
    ob_start();
    $coll->dump();
    ob_get_clean();
});
