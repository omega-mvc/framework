<?php

/**
 * Part of Omega - Tests\Collection Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpConditionAlreadyCheckedInspection */

declare(strict_types=1);

namespace Tests\Collection;

use Omega\Collection\Collection;
use Omega\Collection\CollectionImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function in_array;
use function json_encode;
use function ob_get_clean;
use function ob_start;
use function str_contains;
use function ucfirst;

/**
 * Class CollectionTest
 *
 * This test suite verifies the behavior and functionality of the Collection class
 * from the Omega\Collection package, a mutable collection implementation.
 *
 * The Collection class provides a rich set of features, including:
 *  - Getter and setter access to collection elements.
 *  - Adding, removing, and replacing items.
 *  - Checking for existence of keys or values, counting items, and conditional counts.
 *  - Retrieval of first, last, and subsets of items.
 *  - Iteration and array-like access.
 *  - Functional operations: each, map, filter, reduce, some, every, and shuffle.
 *  - Set operations: diff, diffKeys, diffAssoc, complement, complementKeys, complementAssoc.
 *  - Sorting operations: sort, sortDesc, sortBy, sortByDesc, sortKey, sortKeyDesc.
 *  - Cloning, flattening, chunking, splitting, and selective access (only/except).
 *  - Conversion to immutable CollectionImmutable instances.
 *  - JSON serialization of collection contents.
 *  - Handling of empty collections and null keys/values.
 *
 * This suite ensures that Collection behaves as expected in mutable scenarios,
 * supports chainable operations, and integrates seamlessly with array-like and
 * functional programming paradigms.
 *
 * @category  Tests
 * @package   Collection
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Collection::class)]
#[CoversClass(CollectionImmutable::class)]
final class CollectionTest extends TestCase
{
    /**
     * Test it can get getter setter.
     *
     * @return void
     */
    public function testItCanGetGetterSetter(): void
    {
        $original = [
            'buah_1' => 'mangga',
            'buah_2' => 'jeruk',
            'buah_3' => 'apel',
            'buah_4' => 'melon',
            'buah_5' => 'rambutan',
            'buah_6' => 'peer',
        ];
        $test = new Collection($original);
        $this->assertEquals('mangga', $test->buah_1);
        $this->assertEquals('mangga', $test->get('buah_1'));
        $test->set('buah_7', 'kelengkeng');
        $test->buah_8 = 'cherry';
        $this->assertEquals('cherry', $test->buah_8);
        $this->assertEquals('kelengkeng', $test->get('buah_7'));
        $test->set('buah_7', 'durian');
        $test->buah_8 = 'nanas';
        $this->assertEquals('nanas', $test->buah_8);
        $this->assertEquals('durian', $test->get('buah_7'));
    }

    /**
     * Test it can get add remove has contained.
     *
     * @return void
     */
    public function testItCanGetAddRemoveHasContain(): void
    {
        $original = [
            'buah_1' => 'mangga',
            'buah_2' => 'jeruk',
            'buah_3' => 'apel',
            'buah_4' => 'melon',
            'buah_5' => 'rambutan',
            'buah_6' => 'peer',
        ];
        $test = new Collection($original);
        $this->assertTrue($test->has('buah_1'));
        $this->assertTrue($test->contain('mangga'));
        $test->remove('buah_2');
        $this->assertFalse($test->has('buah_2'));
        $test->replace($original);
    }

    /**
     * Test it can get count and count if.
     *
     * @return void
     */
    public function testItCanGetCountAndCountIf(): void
    {
        $original = [
            'buah_1' => 'mangga',
            'buah_2' => 'jeruk',
            'buah_3' => 'apel',
            'buah_4' => 'melon',
            'buah_5' => 'rambutan',
            'buah_6' => 'peer',
        ];
        $test = new Collection($original);
        $this->assertEquals(6, $test->count());
        $countIf = $test->countIf(function ($item) {
            return str_contains($item, 'e');
        });
        $this->assertEquals(4, $countIf);
    }

    /**
     * Test it can get first last.
     *
     * @eturn void
     */
    public function testItCanGetFirstLast(): void
    {
        $original = [
            'buah_1' => 'mangga',
            'buah_2' => 'jeruk',
            'buah_3' => 'apel',
            'buah_4' => 'melon',
            'buah_5' => 'rambutan',
            'buah_6' => 'peer',
        ];
        $test = new Collection($original);
        $this->assertEquals('mangga', $test->first('bukan buah'));
        $this->assertEquals('peer', $test->last('bukan buah'));
    }

    /**
     * Test it can get clear is empty replace all.
     *
     * @return void
     */
    public function testItCanGetClearIsEmptyReplaceAll(): void
    {
        $original = [
            'buah_1' => 'mangga',
            'buah_2' => 'jeruk',
            'buah_3' => 'apel',
            'buah_4' => 'melon',
            'buah_5' => 'rambutan',
            'buah_6' => 'peer',
        ];
        $test = new Collection($original);
        $this->assertFalse($test->isEmpty());
        $test->clear();
        $this->assertTrue($test->isEmpty());
        $test->replace($original);
        $this->assertEquals($test->all(), $original);
    }

    /**
     * Test it can get keys items.
     *
     * @return void
     */
    public function testItCanGetKeysItems(): void
    {
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
        $this->assertEquals($keys, $test->keys());
        $this->assertEquals($items, $test->items());
    }

    /**
     * Test it can get each map filter.
     *
     * @return void
     */
    public function testItCanGetEachMapFilter(): void
    {
        $original = [
            'buah_1' => 'mangga',
            'buah_2' => 'jeruk',
            'buah_3' => 'apel',
            'buah_4' => 'melon',
            'buah_5' => 'rambutan',
            'buah_6' => 'peer',
        ];
        $test = new Collection($original);
        $test->each(function ($item, $key) use ($original) {
            $this->assertTrue(in_array($item, $original));
            $this->assertArrayHasKey($key, $original);
        });
        $test->map(fn ($item) => ucfirst($item));
        $copy_origin = array_map(fn ($item) => ucfirst($item), $original);
        $this->assertEquals($test->all(), $copy_origin);
        $test->replace($original);
        $test->filter(function ($item) {
            return str_contains($item, 'e');
        });
        $copy_origin = array_filter($original, function ($item) {
            return str_contains($item, 'e');
        });
        $this->assertEquals($test->all(), $copy_origin);
        $test->replace($original);
    }

    /**
     * Test it can get some every.
     *
     * @return void
     */
    public function testItCanGetSomeEvery(): void
    {
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
        $this->assertTrue($some);
        $every = $test->every(function ($item) {
            return !str_contains($item, 'x');
        });
        $this->assertTrue($every);
    }

    /**
     * Test it can get JSON.
     *
     * @return void
     */
    public function testItCanGetJson(): void
    {
        $original = [
            'buah_1' => 'mangga',
            'buah_2' => 'jeruk',
            'buah_3' => 'apel',
            'buah_4' => 'melon',
            'buah_5' => 'rambutan',
            'buah_6' => 'peer',
        ];
        $test = new Collection($original);
        $json = json_encode($original);
        $this->assertJsonStringEqualsJsonString($test->json(), $json);
    }

    /**
     * Test it can get reverse sort.
     *
     * @return void
     */
    public function testItCanGetReverseSort(): void
    {
        $original = [
            'buah_1' => 'mangga',
            'buah_2' => 'jeruk',
            'buah_3' => 'apel',
            'buah_4' => 'melon',
            'buah_5' => 'rambutan',
            'buah_6' => 'peer',
        ];
        $test        = new Collection($original);
        $copy_origin = $original;
        $this->assertEquals($test->reverse()->all(), array_reverse($copy_origin));
        $test->replace($original);
        $this->assertEquals('apel', $test->sort()->first());
        $this->assertEquals('rambutan', $test->sortDesc()->first());
        $test->sortBy(function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });
        $this->assertEquals('apel', $test->first());
        $test->sortByDesc(function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });
        $this->assertEquals('rambutan', $test->first());
        $this->assertEquals('mangga', $test->sortKey()->first());
        $this->assertEquals('peer', $test->sortKeyDesc()->first());
        $test->replace($original);
    }

    /**
     * Test it can get clone reject chunk split only except flatten.
     *
     * @return void
     */
    public function testItCanGetCloneRejectChunkSplitOnlyExceptFlatten(): void
    {
        $original = [
            'buah_1' => 'mangga',
            'buah_2' => 'jeruk',
            'buah_3' => 'apel',
            'buah_4' => 'melon',
            'buah_5' => 'rambutan',
            'buah_6' => 'peer',
        ];
        $test = new Collection($original);
        $this->assertEquals($test->clone()->reverse()->first(), $test->last());
        $copy_origin = $original;
        unset($copy_origin['buah_2']);
        $this->assertEquals($test->reject(fn ($item) => $item == 'jeruk')->all(), $copy_origin);
        $chunk = $test->clone()->chunk(3)->all();
        $this->assertEquals([
            ['buah_1' => 'mangga', 'buah_3' => 'apel', 'buah_4' => 'melon'],
            ['buah_5' => 'rambutan', 'buah_6' => 'peer'],
        ], $chunk);
        $split = $test->clone()->split(3)->all();
        $this->assertEquals([
            ['buah_1' => 'mangga', 'buah_3' => 'apel'],
            ['buah_4' => 'melon', 'buah_5' => 'rambutan'],
            ['buah_6' => 'peer'],
        ], $split);
        $only = $test->clone()->only(['buah_1', 'buah_5']);
        $this->assertEquals(['buah_1' => 'mangga', 'buah_5' => 'rambutan'], $only->all());
        $except = $test->clone()->except(['buah_3', 'buah_4', 'buah_6']);
        $this->assertEquals(['buah_1' => 'mangga', 'buah_5' => 'rambutan'], $except->all());
        $array_nesting = [
            'first' => ['buah_1' => 'mangga', ['buah_2' => 'jeruk', 'buah_3' => 'apel', 'buah_4' => 'melon']],
            'mid'   => ['buah_4' => 'melon', ['buah_5' => 'rambutan']],
            'last'  => ['buah_6' => 'peer'],
        ];
        $flatten = new Collection($array_nesting);
        $this->assertEquals($original, $flatten->flatten()->all());
    }

    /**
     * Test it collection chain work great.
     *
     * @return void
     */
    public function testItCollectionChainWorkGreat(): void
    {
        $origin     = [0, 1, 2, 3, 4];
        $collection = new Collection($origin);

        $chain = $collection
            ->add($origin)
            ->remove(0)
            ->set(0, 0)
            ->clear()
            ->replace($origin)
            ->each(fn ($el) => in_array($el, $origin))
            ->map(fn ($el) => $el + 100 - (2 * 50)) // equal +0
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
            ->all()
        ;

        $this->assertEquals($chain, $origin, 'all collection with chain is work');
    }

    /**
     * Test it can add collection from collection.
     *
     * @return void
     */
    public function testItCanAddCollectionFromCollection(): void
    {
        $arr_1 = ['a' => 'b'];
        $arr_2 = ['c' => 'd'];

        $collect_1 = new Collection($arr_1);
        $collect_2 = new CollectionImmutable($arr_2);

        $collect = new Collection([]);
        $collect->ref($collect_1)->ref($collect_2);

        $this->assertEquals(['a' => 'b', 'c' => 'd'], $collect->all());
    }

    /**
     * Test it can act like array.
     *
     * @return void
     */
    public function testItCanActingLikeArray(): void
    {
        $coll = new Collection(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->assertArrayHasKey('one', $coll);
        $this->assertArrayHasKey('two', $coll);
        $this->assertArrayHasKey('three', $coll);
    }

    /**
     * Test it can do like array.
     *
     * @return void
     */
    public function testItCanDoLikeArray(): void
    {
        $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
        $coll = new Collection($arr);

        // get
        foreach ($arr as $key => $value) {
            $this->assertEquals($value, $coll[$key]);
        }

        // set
        $coll['four'] = 4;
        $this->assertArrayHasKey('four', $coll);

        // has
        $this->assertTrue(isset($coll['four']));

        // unset
        unset($coll['four']);
        $this->assertEquals($arr, $coll->all());
    }

    /**
     * Test it can be iterator.
     *
     * @return void
     */
    public function testItCanBeIterator(): void
    {
        $coll = new Collection(['one' => 1, 'two' => 2, 'three' => 3]);

        foreach ($coll as $key => $value) {
            $this->assertEquals($value, $coll[$key]);
        }
    }

    /**
     * Test it can be shuffled.
     *
     * @return void
     */
    public function testItCanBeShuffled(): void
    {
        $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
        $coll = new Collection($arr);

        $coll->shuffle();

        foreach ($arr as $key => $val) {
            $this->assertArrayHasKey($key, $coll);
        }
    }

    /**
     * Test it can map with keys.
     *
     * @return void
     */
    public function testItCanMapWithKeys(): void
    {
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

        $this->assertEquals([
            'taylor'     => 'taylor@laravel.com',
            'giovannini' => 'giovannini@savanna.com',
        ], $assocBy->toArray());
    }

    /**
     * Test it can clone collection.
     *
     * @return void
     */
    public function testItCanCloneCollection(): void
    {
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
        $this->assertEquals('one', $clone->get('one'));

        $clone->set('one', 1);
        $this->assertEquals('uno', $ori->get('one'));
    }

    /**
     * Test it can get sum using reduce.
     *
     * @return void
     */
    public function testItCanGetSumUsingReduce(): void
    {
        $collection = new Collection([1, 2, 3, 4]);

        $sum = $collection->reduce(fn ($carry, $item) => $carry + $item);

        $this->assertTrue($sum === 10);
    }

    /**
     * Test it can get take first.
     *
     * @return void
     */
    public function testItCanGetTakeFirst(): void
    {
        $coll = new Collection([10, 20, 30, 40, 50, 60, 70, 80, 90]);

        $this->assertEquals([10, 20], $coll->take(2)->toArray());
    }

    /**
     * Test it can get take last.
     *
     * @return void
     */
    public function testItCanGetTakeLast(): void
    {
        $coll = new Collection([10, 20, 30, 40, 50, 60, 70, 80, 90]);

        $this->assertEquals([80, 90], $coll->take(-2)->toArray());
    }

    /**
     * Test it can push new item.
     *
     * @return void
     */
    public function testItCanPushNewItem(): void
    {
        $coll = new Collection([10, 20, 30, 40, 50, 60, 70, 80, 90]);
        $coll->push(100);

        $this->assertTrue(in_array(100, $coll->toArray()));
    }

    /**
     * Test it can get diff.
     *
     * @return void
     */
    public function testItCanGetDiff(): void
    {
        $coll = new Collection([1, 2, 3, 4, 5]);
        $coll->diff([2, 4, 6, 8]);

        $this->assertEquals([1, 3, 5], $coll->items());
    }

    /**
     * Test it can get diff using key.
     *
     * @return void
     */
    public function testItCanGetDiffUsingKey(): void
    {
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

        $this->assertEquals([
            'buah_1' => 'mangga',
            'buah_3' => 'apel',
            'buah_5' => 'rambutan',
        ], $coll->toArray());
    }

    /**
     * Test it can get diff using assoc.
     *
     * @return void
     */
    public function testItCanGetDiffUsingAssoc(): void
    {
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

        $this->assertEquals([
            'color'   => 'green',
            'type'    => 'library',
            'version' => 0,
        ], $coll->toArray());
    }

    /**
     * Test it can get complements.
     *
     * @return void
     */
    public function testItCanGetComplement(): void
    {
        $coll = new Collection([1, 2, 3, 4, 5]);
        $coll->complement([2, 4, 6, 8]);

        $this->assertEquals([6, 8], $coll->items());
    }

    /**
     * Test it can get complement using key.
     *
     * @return void
     */
    public function testItCanGetComplementUsingKey(): void
    {
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

        $this->assertEquals([
            'buah_6' => 'six',
            'buah_8' => 'eight',
        ], $coll->toArray());
    }

    /**
     * Test it can get complements using assoc.
     *
     * @return void
     */
    public function testItCanGetComplementUsingAssoc(): void
    {
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

        $this->assertEquals([
            'color'   => 'orange',
            'type'    => 'framework',
            'version' => 10,
            'used'    => 100,
        ], $coll->toArray());
    }

    /**
     * Test it can get filtered using where.
     *
     * @return void
     */
    public function testItCanGetFilteredUsingWhere(): void
    {
        $data = [
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
            ['user' => 'user4', 'age' => 13],
            ['user' => 'user5', 'age' => 14],
        ];
        $equal = new Collection($data)->where('age', '=', '13');
        $this->assertEquals([
            3 => ['user' => 'user4', 'age' => 13],
        ], $equal->toArray());

        $identical = new Collection($data)->where('age', '===', 13);
        $this->assertEquals([
            3 => ['user' => 'user4', 'age' => 13],
        ], $identical->toArray());

        $notequal = new Collection($data)->where('age', '!=', '13');
        $this->assertEquals([
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
            4       => ['user' => 'user5', 'age' => 14],
        ], $notequal->toArray());

        $notEqualIdentical = new Collection($data)->where('age', '!==', 13);
        $this->assertEquals([
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
            4       => ['user' => 'user5', 'age' => 14],
        ], $notEqualIdentical->toArray());

        $greaterThan = new Collection($data)->where('age', '>', 13);
        $this->assertEquals([
            4 => ['user' => 'user5', 'age' => 14],
        ], $greaterThan->toArray());

        $greaterThanEqual = new Collection($data)->where('age', '>=', 13);
        $this->assertEquals([
            3 => ['user' => 'user4', 'age' => 13],
            4 => ['user' => 'user5', 'age' => 14],
        ], $greaterThanEqual->toArray());

        $lessThan = new Collection($data)->where('age', '<', 13);
        $this->assertEquals([
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
        ], $lessThan->toArray());

        $lessThanEqual = new Collection($data)->where('age', '<=', 13);
        $this->assertEquals([
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
            ['user' => 'user4', 'age' => 13],
        ], $lessThanEqual->toArray());
    }

    /**
     * Test it can filter data using where in.
     *
     * @return void
     */
    public function testItCanFilterDataUsingWhereIn(): void
    {
        $data = [
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
            ['user' => 'user4', 'age' => 13],
            ['user' => 'user5', 'age' => 14],
        ];

        $wherein = new Collection($data)->whereIn('age', [10, 12]);
        $this->assertEquals([
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
        ], $wherein->toArray());
    }

    /**
     * Test it can filter data using where not in.
     *
     * @return void
     */
    public function testItCanFilterDataUsingWhereNotIn(): void
    {
        $data = [
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
            ['user' => 'user4', 'age' => 13],
            ['user' => 'user5', 'age' => 14],
        ];

        $wherein = new Collection($data)->whereNotIn('age', [13, 14]);
        $this->assertEquals([
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
        ], $wherein->toArray());
    }

    /**
     * Test it can convert to immutable.
     *
     * @return void
     */
    public function testItCanConvertToImmutable(): void
    {
        $coll      = new Collection(['a' => 1, 'b' => 2]);
        $immutable = $coll->immutable();
        $this->assertInstanceOf(CollectionImmutable::class, $immutable);
        $this->assertEquals(['a' => 1, 'b' => 2], $immutable->all());
    }

    /**
     * Test it can handle empty collection.
     *
     * @return void
     */
    public function testItCanHandleEmptyCollection(): void
    {
        $coll = new Collection([]);
        $this->assertTrue($coll->isEmpty());
        $this->assertNull($coll->first());
        $this->assertNull($coll->last());
        $this->assertEquals([], $coll->keys());
        $this->assertEquals([], $coll->items());
    }

    /**
     * Test it can handle null kwy and value.
     *
     * @return void
     */
    public function testItCanHandleNullKeyAndValue(): void
    {
        $coll = new Collection([null => null]);
        $this->assertTrue($coll->has(null));
        $this->assertNull($coll->get(null));
    }

    /**
     * Test it can dump without error.
     *
     * @return void
     */
    public function testItCanDumpWithoutError(): void
    {
        $coll = new Collection(['a' => 1]);

        $this->expectNotToPerformAssertions();
        ob_start();
        $coll->dump();
        ob_get_clean();
    }
}
