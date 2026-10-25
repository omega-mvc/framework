<?php

declare(strict_types=1);

namespace Tests\Collection;

use Omega\Collection\Collection;
use Omega\Collection\CollectionImmutable;
use Tests\TestCase;

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

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class CollectionTest extends TestCase
{
    public function testCanGetGetterSetter(): void
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

    public function testCanGetAddRemoveHasContain(): void
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

    public function testCanGetCountAndCountIf(): void
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

        $countIf = $test->countIf(function (string $item) {
            return str_contains($item, 'e');
        });

        $this->assertEquals(4, $countIf);
    }

    public function testCanGetFirstLast(): void
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

    public function testCanGetClearIsEmptyReplaceAll(): void
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

        $this->assertEquals($original, $test->all());
    }

    public function testCanGetKeysItems(): void
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

    public function testCanGetEachMapFilter(): void
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

        $test->each(function (string $item, string $key = '') use ($original) {
            $this->assertContains($item, $original);
            $this->assertArrayHasKey($key, $original);
        });

        $test->map(fn (string $item) => ucfirst($item));

        $copyOrigin = array_map(fn (string $item) => ucfirst($item), $original);

        $this->assertEquals($copyOrigin, $test->all());

        $test->replace($original);

        $test->filter(function (string $item) {
            return str_contains($item, 'e');
        });

        $copyOrigin = array_filter($original, function (string $item) {
            return str_contains($item, 'e');
        });

        $this->assertEquals($copyOrigin, $test->all());

        $test->replace($original);
    }

    public function testCanGetSomeEvery(): void
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

        $some = $test->some(function (string $item) {
            return str_contains($item, 'e');
        });

        $this->assertTrue($some);

        $every = $test->every(function (string $item) {
            return !str_contains($item, 'x');
        });

        $this->assertTrue($every);
    }

    public function testCanGetJson(): void
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
        $json = json_encode($original, JSON_THROW_ON_ERROR);

        $this->assertSame($json, $test->json());
    }

    public function testCanGetReverseSort(): void
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
        $copyOrigin = $original;

        $this->assertEquals(array_reverse($copyOrigin), $test->reverse()->all());

        $test->replace($original);

        $this->assertEquals('apel', $test->sort()->first());
        $this->assertEquals('rambutan', $test->sortDesc()->first());

        $test->sortBy(function (string $a, string $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        $this->assertEquals('apel', $test->first());

        $test->sortByDesc(function (string $a, string $b) {
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

    public function testCanGetCloneRejectChunkSplitOnlyExceptFlatten(): void
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

        $this->assertEquals($test->last(), $test->clone()->reverse()->first());

        $copyOrigin = $original;
        unset($copyOrigin['buah_2']);

        $this->assertEquals($copyOrigin, $test->reject(fn (string $item) => $item == 'jeruk')->all());

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

        $arrayNesting = [
            'first' => ['buah_1' => 'mangga', ['buah_2' => 'jeruk', 'buah_3' => 'apel', 'buah_4' => 'melon']],
            'mid'   => ['buah_4' => 'melon', ['buah_5' => 'rambutan']],
            'last'  => ['buah_6' => 'peer'],
        ];
        $flatten = new Collection($arrayNesting);

        $this->assertEquals($original, $flatten->flatten()->all());
    }

    public function testCollectionChainWorkGreat(): void
    {
        $origin     = [0, 1, 2, 3, 4];
        $collection = new Collection($origin);

        $chain = $collection
            ->add($origin)
            ->remove(0)
            ->set(0, 0)
            ->clear()
            ->replace($origin)
            ->each(fn (int $el) => in_array($el, $origin))
            ->map(fn (int $el) => $el + 100 - (2 * 50))
            ->filter(fn (int $el) => $el > -1)
            ->sort()
            ->sortDesc()
            ->sortKey()
            ->sortKeyDesc()
            ->sortBy(function (int $a, int $b) {
                if ($a == $b) {
                    return 0;
                }

                return ($a < $b) ? -1 : 1;
            })
            ->sortByDesc(function (int $a, int $b) {
                if ($b == $a) {
                    return 0;
                }

                return ($b < $a) ? -1 : 1;
            })
            ->all();

        $this->assertEquals($origin, $chain);
    }

    public function testCanAddCollectionFromCollection(): void
    {
        $arr1 = ['a' => 'b'];
        $arr2 = ['c' => 'd'];

        $collect1 = new Collection($arr1);
        $collect2 = new CollectionImmutable($arr2);

        $collect = (new Collection($arr1))->clear();
        $collect->ref($collect1)->ref($collect2);

        $this->assertEquals(['a' => 'b', 'c' => 'd'], $collect->all());
    }

    public function testCanActLikeArray(): void
    {
        $coll = new Collection(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->assertTrue(isset($coll['one']));
        $this->assertTrue(isset($coll['two']));
        $this->assertTrue(isset($coll['three']));
    }

    public function testCanDoLikeArray(): void
    {
        $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
        $coll = new Collection($arr);

        foreach ($arr as $key => $value) {
            $this->assertEquals($value, $coll[$key]);
        }

        $coll['four'] = 4;

        $this->assertTrue(isset($coll['four']));

        unset($coll['four']);

        $this->assertEquals($arr, $coll->all());
    }

    public function testCanBeIterator(): void
    {
        $coll = new Collection(['one' => 1, 'two' => 2, 'three' => 3]);

        foreach ($coll as $key => $value) {
            $this->assertEquals($value, $coll[$key]);
        }
    }

    public function testCanBeShuffled(): void
    {
        $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
        $coll = new Collection($arr);

        $coll->shuffle();

        foreach ($arr as $key => $val) {
            $this->assertTrue($coll->has($key));
        }
    }

    public function testCanMapWithKeys(): void
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

        $assocBy = $arr->assocBy(fn (array $item) => [$item['name'] => $item['email']]);

        $this->assertEquals([
            'taylor'     => 'taylor@laravel.com',
            'giovannini' => 'giovannini@savanna.com',
        ], $assocBy->toArray());
    }

    public function testCanCloneCollection(): void
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

    public function testCanGetSumUsingReduce(): void
    {
        $collection = new Collection([1, 2, 3, 4]);

        $sum = $collection->reduce(fn (?int $carry, int $item) => $carry + $item);

        $this->assertTrue($sum === 10);
    }

    public function testCanGetTakeFirst(): void
    {
        $coll = new Collection([10, 20, 30, 40, 50, 60, 70, 80, 90]);

        $this->assertEquals([10, 20], $coll->take(2)->toArray());
    }

    public function testCanGetTakeLast(): void
    {
        $coll = new Collection([10, 20, 30, 40, 50, 60, 70, 80, 90]);

        $this->assertEquals([80, 90], $coll->take(-2)->toArray());
    }

    public function testCanPushNewItem(): void
    {
        $coll = new Collection([10, 20, 30, 40, 50, 60, 70, 80, 90]);
        $coll->push(100);

        $this->assertTrue(in_array(100, $coll->toArray()));
    }

    public function testCanGetDiff(): void
    {
        $coll = new Collection([1, 2, 3, 4, 5]);
        $coll->diff([2, 4, 6, 8]);

        $this->assertEquals([1, 3, 5], $coll->items());
    }

    public function testCanGetDiffUsingKey(): void
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

    public function testCanGetDiffUsingAssoc(): void
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

    public function testCanGetComplement(): void
    {
        $coll = new Collection([1, 2, 3, 4, 5]);
        $coll->complement([2, 4, 6, 8]);

        $this->assertEquals([6, 8], $coll->items());
    }

    public function testCanGetComplementUsingKey(): void
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

    public function testCanGetComplementUsingAssoc(): void
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

    public function testCanGetFilteredUsingWhere(): void
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

        $notEqual = new Collection($data)->where('age', '!=', '13');
        $this->assertEquals([
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
            4       => ['user' => 'user5', 'age' => 14],
        ], $notEqual->toArray());

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

    public function testCanFilterDataUsingWhereIn(): void
    {
        $data = [
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
            ['user' => 'user4', 'age' => 13],
            ['user' => 'user5', 'age' => 14],
        ];

        $whereIn = new Collection($data)->whereIn('age', [10, 12]);

        $this->assertEquals([
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
        ], $whereIn->toArray());
    }

    public function testCanFilterDataUsingWhereNotIn(): void
    {
        $data = [
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
            ['user' => 'user4', 'age' => 13],
            ['user' => 'user5', 'age' => 14],
        ];

        $whereNotIn = new Collection($data)->whereNotIn('age', [13, 14]);

        $this->assertEquals([
            ['user' => 'user1', 'age' => 10],
            ['user' => 'user2', 'age' => 12],
            ['user' => 'user3', 'age' => 10],
        ], $whereNotIn->toArray());
    }

    public function testCanConvertToImmutable(): void
    {
        $coll      = new Collection(['a' => 1, 'b' => 2]);
        $immutable = $coll->immutable();

        $this->assertInstanceOf(CollectionImmutable::class, $immutable);
        $this->assertEquals(['a' => 1, 'b' => 2], $immutable->all());
    }

    public function testCanHandleEmptyCollection(): void
    {
        $coll = (new Collection(['a' => 1]))->clear();

        $this->assertTrue($coll->isEmpty());
        $this->assertNull($coll->first());
        $this->assertNull($coll->last());
        $this->assertEquals([], $coll->keys());
        $this->assertEquals([], $coll->items());
    }

    public function testCanHandleNullKeyAndValue(): void
    {
        $coll = new Collection(['' => null]);

        $this->assertTrue($coll->has(null));
    }

    public function testCanDumpWithoutError(): void
    {
        $coll = new Collection(['a' => 1]);

        $this->expectNotToPerformAssertions();
        ob_start();
        $coll->dump();
        ob_get_clean();
    }

    public function testThrowsWhenChunkingWithANonPositiveLength(): void
    {
        $coll = new Collection([1, 2, 3]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chunk length must be greater than zero.');

        $coll->chunk(0);
    }

    public function testCanFlattenALimitedDepth(): void
    {
        $coll = new Collection([[1, 2], [3, [4, 5]]]);

        $this->assertEquals([1, 2, 3, [4, 5]], $coll->flatten(1)->all());
        $this->assertEquals([1, 2, 3, 4, 5], $coll->flatten(2)->all());
    }

    public function testCanFlattenWithoutDroppingSiblingArrays(): void
    {
        $coll = new Collection([[1, 2], [3, 4]]);

        $this->assertEquals([1, 2, 3, 4], $coll->flatten()->all());
        $this->assertEquals([1, 2, 3, 4, 5, 6], (new Collection([1, [2, 3], [4, 5], 6]))->flatten()->all());
    }

    public function testCanFlattenDeeplyNestedCollections(): void
    {
        $coll = new Collection([1, [2, [3, [4]]]]);

        $this->assertEquals([1, 2, 3, [4]], $coll->flatten(2)->all());
        $this->assertEquals([1, 2, 3, 4], $coll->flatten()->all());
    }

    public function testCanFlattenWhilePreservingAssocLeafKeys(): void
    {
        $coll = new Collection([
            'first' => ['x' => 1, 'y' => 2],
            'last'  => ['z' => 3],
        ]);

        $this->assertEquals(['x' => 1, 'y' => 2, 'z' => 3], $coll->flatten()->all());
    }

    public function testCanFlattenCollectionsNestedInsideACollection(): void
    {
        $coll = new Collection([new Collection([9]), [1, [2]]]);

        $this->assertEquals([9, 1, 2], $coll->flatten()->all());
    }

    public function testCanFlattenEmptySubarrays(): void
    {
        $coll = new Collection([[], [1, 2]]);

        $this->assertEquals([1, 2], $coll->flatten()->all());
    }

    public function testCanStringifyObjectsWhenComparing(): void
    {
        $value = new class {
            public function __toString(): string
            {
                return 'x';
            }
        };

        $coll = new Collection([1, 2, $value]);
        $coll->diff([$value]);

        $this->assertEquals([1, 2], $coll->items());
    }

    public function testIgnoresNonStringifiableValuesInAssocDiff(): void
    {
        $coll = new Collection(['a' => 'keep', 'x' => [1, 2]]);
        $coll->diffAssoc(['x' => [1, 2]]);

        $this->assertEquals(['a' => 'keep', 'x' => [1, 2]], $coll->all());
    }

    public function testClearsTheCollectionForAnUnsupportedWhereOperator(): void
    {
        $coll = new Collection(['a', 'b']);

        $this->assertTrue($coll->where('age', 'like', '1')->isEmpty());
    }

    public function testCanOnlyKeepEveryRequestedKey(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $coll->only(['a', 'b', 'c'])->all());
    }

    public function testCanOnlyKeepKeysThatAreAllAbsent(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2]);

        $this->assertEquals([], $coll->only(['x', 'y'])->all());
    }

    public function testCanOnlyKeepKeysFromAnEmptySelection(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2]);

        $this->assertEquals([], $coll->only([])->all());
    }

    public function testCanExceptNothing(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2]);

        $this->assertEquals(['a' => 1, 'b' => 2], $coll->except([])->all());
    }

    public function testCanExceptKeysThatAreAllAbsent(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2]);

        $this->assertEquals(['a' => 1, 'b' => 2], $coll->except(['x', 'y'])->all());
    }

    public function testCanExceptEveryKey(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2]);

        $this->assertEquals([], $coll->except(['a', 'b'])->all());
    }

    public function testCanComplementWithAnEmptyArray(): void
    {
        $coll = new Collection([1, 2, 3]);

        $this->assertEquals([], $coll->complement([])->items());
    }

    public function testCanComplementWhenEveryItemOverlaps(): void
    {
        $coll = new Collection([1, 2, 3]);

        $this->assertEquals([], $coll->complement([1, 2, 3])->items());
    }

    public function testCanComplementWhenASingleItemOverlaps(): void
    {
        $coll = new Collection([1, 2, 3]);

        $this->assertEquals([9], $coll->complement([3, 9])->items());
    }

    public function testCanDiffWithAnEmptyArray(): void
    {
        $coll = new Collection([1, 2, 3]);

        $coll->diff([]);

        $this->assertEquals([1, 2, 3], $coll->items());
    }

    public function testCanDiffWhenEveryItemOverlaps(): void
    {
        $coll = new Collection([1, 2, 3]);

        $coll->diff([1, 2, 3]);

        $this->assertEquals([], $coll->items());
    }

    public function testCanDiffKeysWithAnEmptySelector(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2]);

        $coll->diffKeys([]);

        $this->assertEquals(['a' => 1, 'b' => 2], $coll->all());
    }

    public function testCanComplementKeysWithNoOverlap(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2]);

        $this->assertEquals(['x' => 9], $coll->complementKeys(['x' => 9])->all());
    }

    public function testCanDiffAssocWhenKeysAndValuesAreIdentical(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2]);

        $coll->diffAssoc(['a' => 1, 'b' => 2]);

        $this->assertEquals([], $coll->all());
    }

    public function testCanComplementAssocWhenKeysAndValuesAreIdentical(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2]);

        $this->assertEquals([], $coll->complementAssoc(['a' => 1, 'b' => 2])->all());
    }

    public function testCanMapOverAnEmptyCollection(): void
    {
        $coll = new Collection([]);

        $this->assertEquals([], $coll->map(static fn (int $item): int => $item + 1)->all());
    }

    public function testCanMapOverASingleItem(): void
    {
        $coll = new Collection([5]);

        $this->assertEquals([10], $coll->map(static fn (int $item): int => $item * 2)->all());
    }

    public function testCanGetWithANullKey(): void
    {
        $coll = new Collection(['' => 'empty']);

        $this->assertSame('empty', $coll->get(null));
        $this->assertSame('empty', $coll->get(null, 'fallback'));

        $this->assertSame('fallback', (new Collection([]))->get(null, 'fallback'));
    }

    public function testCanAssocByWithACallbackReturningAnEmptyArray(): void
    {
        $coll = new Collection(['a' => 1]);

        $this->assertSame([], $coll->assocBy(static fn (): array => [])->all());
    }
    public function testCanRemoveAKeyThatDoesNotExist(): void
    {
        $coll = new Collection(['a' => 1]);

        $coll->remove('missing');

        $this->assertSame(['a' => 1], $coll->all());
    }

    public function testCanFilterAndRejectCollectionsOfAnySize(): void
    {
        $batches = [
            [],
            [1],
            [1, 2],
            [1, 2, 3],
            [1, 2, 3, 4],
        ];

        $even = static fn (int $item): bool => $item % 2 === 0;
        $odd  = static fn (int $item): bool => $item % 2 !== 0;

        foreach ($batches as $items) {
            $this->assertSame(
                array_values(array_filter($items, $even)),
                array_values((new Collection($items))->filter($even)->all())
            );
            $this->assertSame(
                array_values(array_filter($items, $odd)),
                array_values((new Collection($items))->reject($even)->all())
            );
        }
    }

    public function testCanExceptAndOnlyOnAnEmptyCollection(): void
    {
        $this->assertSame([], (new Collection([]))->except(['a'])->all());
        $this->assertSame([], (new Collection([]))->only(['a'])->all());
    }

    public function testCanExceptAndOnlyCollectionsWithSeveralItems(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);

        $this->assertSame(['b' => 2, 'd' => 4], $coll->except(['a', 'c'])->all());
        $this->assertSame(
            ['a' => 1, 'c' => 3],
            (new Collection(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]))->only(['a', 'c', 'z'])->all()
        );
    }

    public function testCanAssocByOnAnEmptyCollectionAndWithSeveralItems(): void
    {
        $this->assertSame([], (new Collection([]))->assocBy(static fn (): array => [])->all());

        $coll = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
        $result = $coll->assocBy(
            static fn (int $item): array => $item === 2 ? [] : ['k' . $item => $item]
        );

        $this->assertSame(['k1' => 1, 'k3' => 3], $result->all());
    }

    public function testCanDiffAndComplementAssocOnEmptyCollections(): void
    {
        /** @var array<string, int> $nothing */
        $nothing = [];

        $this->assertSame([], (new Collection($nothing))->diffAssoc(['a' => 1])->all());
        $this->assertSame([], (new Collection([]))->complementAssoc([])->all());
        $this->assertSame([], (new Collection(['a' => 1]))->complementAssoc(['a' => 1])->all());
        $this->assertSame([], (new Collection(['a' => 1]))->diffAssoc(['a' => 1])->all());
    }

    public function testCanDiffAndComplementAssocWithSeveralItems(): void
    {
        $coll = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertSame(['a' => 1, 'c' => 3], $coll->diffAssoc(['b' => 2, 'd' => 4])->all());

        $coll = new Collection(['a' => 1, 'c' => 3]);

        $this->assertSame(['b' => 2, 'd' => 4], $coll->complementAssoc(['a' => 1, 'b' => 2, 'd' => 4])->all());
    }

    public function testCanCountIfOnEmptyAndSeveralItems(): void
    {
        $this->assertSame(0, (new Collection([]))->countIf(static fn (int $item): bool => true));
        $this->assertSame(
            2,
            (new Collection([1, 2, 3, 4]))->countIf(static fn (int $item): bool => $item % 2 === 0)
        );
    }

    public function testCanEachOnEmptyAndStopsEarly(): void
    {
        $visited = [];

        (new Collection([]))->each(static function (int $item) use (&$visited): void {
            $visited[] = $item;
        });

        $this->assertSame([], $visited);

        $visited = [];

        (new Collection([1, 2, 3, 4]))->each(static function (int $item) use (&$visited): bool {
            $visited[] = $item;

            return $item < 2;
        });

        $this->assertSame([1, 2], $visited);
    }

    public function testCanCheckSomeAndEveryOnEmptyAndSeveralItems(): void
    {
        $this->assertFalse((new Collection([]))->some(static fn (int $item): bool => true));
        $this->assertTrue((new Collection([]))->every(static fn (int $item): bool => true));
        $this->assertFalse((new Collection([1, 2, 3, 4]))->some(static fn (int $item): bool => $item > 10));
        $this->assertTrue((new Collection([1, 2, 3, 4]))->every(static fn (int $item): bool => $item > 0));
        $this->assertTrue((new Collection([1, 2, 3, 4]))->some(static fn (int $item): bool => $item === 4));
        $this->assertFalse((new Collection([1, 2, 3, 4]))->every(static fn (int $item): bool => $item < 4));
    }

    public function testCanPluckFromEmptyAndMixedCollections(): void
    {
        $this->assertSame([], (new Collection([]))->pluck('id'));

        $items = [
            ['id' => 1, 'name' => 'a'],
            (object) ['id' => 2, 'name' => 'b'],
            'scalar',
            ['name' => 'c'],
        ];

        $this->assertSame([1, 2, null], (new Collection($items))->pluck('id'));
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => null], (new Collection($items))->pluck('id', 'name'));
    }

    public function testCanFlattenAnEmptyCollection(): void
    {
        $this->assertSame([], (new Collection([]))->flatten()->all());
    }

    public function testCanFlattenSiblingArraysWithCollidingKeys(): void
    {
        $this->assertSame([1, 2, 3, 4], (new Collection([[1], [2], [3], [4]]))->flatten()->all());
    }

    public function testCanFlattenWithDepthOneAndMixedItems(): void
    {
        $coll = new Collection([
            'a' => [1, 2],
            'b' => 'scalar',
            'c' => new Collection(['x' => 3]),
        ]);

        $this->assertSame([0 => 1, 1 => 2, 'b' => 'scalar', 2 => 3], $coll->flatten(1)->all());
    }

    public function testShortCircuitsOnTheFirstItem(): void
    {
        $items = new Collection([1, 2, 3, 4]);

        $this->assertTrue($items->some(static fn (int $item): bool => $item === 1));
        $this->assertFalse($items->every(static fn (int $item): bool => $item > 1));

        $visited = [];

        $items->each(static function (int $item) use (&$visited): bool {
            $visited[] = $item;

            return false;
        });

        $this->assertSame([1], $visited);
        $this->assertSame(1, (new Collection([1, 2, 3]))->countIf(static fn (int $item): bool => $item === 1));
        $this->assertSame(2, (new Collection([1, 2, 3]))->countIf(static fn (int $item): bool => $item !== 2));
    }

    public function testCanFlattenWithCollectionInstancesAndDepthOne(): void
    {
        $coll = new Collection([
            new Collection([1, 2]),
            new Collection(['x' => 3]),
        ]);

        $this->assertSame([1, 2, 2 => 3], $coll->flatten(1)->all());
    }

    public function testCanFlattenWithStringKeysAndNumericCollisions(): void
    {
        $coll = new Collection([
            ['a' => 1, 'b' => 2],
            ['c' => 3],
            [0 => 99],
        ]);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3, 99], $coll->flatten()->all());
    }

    public function testCanFlattenWhenNumericChildKeysCollide(): void
    {
        $coll = new Collection([
            ['x', 'y'],
            ['z'],
        ]);

        $this->assertSame(['x', 'y', 'z'], $coll->flatten()->all());
    }

    public function testCanFlattenWithMixedStringAndNumericKeysDeeply(): void
    {
        $coll = new Collection([
            'root' => ['nested' => [10, 20]],
            ['a' => 30],
        ]);

        $this->assertSame([[10, 20], 30], $coll->flatten(1)->all());
    }

    public function testCanFlattenCollectionContainingCollectionAtDepthOne(): void
    {
        $coll = new Collection([
            ['list' => new Collection([1, 2, 3])],
        ]);

        $result = $coll->flatten(1)->all();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Collection::class, $result[0]);
        $this->assertSame([1, 2, 3], $result[0]->all());
    }

    public function testCanFlattenWithScalarValuesDirectly(): void
    {
        $coll = new Collection([1, 2, 'hello', null]);
        $this->assertSame([1, 2, 'hello', null], $coll->flatten()->all());
    }

    public function testCanFlattenDeeplyNestedWithStringKeys(): void
    {
        $coll = new Collection([
            ['a' => ['b' => ['c' => 1]]],
        ]);
        $this->assertSame(['c' => 1], $coll->flatten()->all());
    }

    public function testCanFlattenWithMultipleStringKeyCollisions(): void
    {
        $coll = new Collection([
            ['key' => 1],
            ['key' => 2],
            ['other' => 3],
        ]);
        $this->assertSame(['key' => 2, 'other' => 3], $coll->flatten()->all());
    }

    public function testCanFlattenDepthOneWithNumericKeysAndCollision(): void
    {
        $coll = new Collection([
            [1, 2],
            [3, 4],
        ]);
        $this->assertSame([1, 2, 3, 4], $coll->flatten(1)->all());
    }

    public function testCanFlattenMixedCollectionsAndArrays(): void
    {
        $coll = new Collection([
            new Collection(['a' => [1, 2]]),
            ['b' => [3, 4]],
        ]);
        $this->assertSame([[1, 2], [3, 4]], $coll->flatten(1)->all());
    }

    public function testCanFlattenWithEmptyArrayInMiddle(): void
    {
        $coll = new Collection([1, [], [2, 3], []]);
        $this->assertSame([1, 2, 3], $coll->flatten()->all());
    }

    public function testCanFlattenWithAllStringKeysNoCollision(): void
    {
        $coll = new Collection([
            ['a' => 1],
            ['b' => 2],
            ['c' => 3],
        ]);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $coll->flatten()->all());
    }

    public function testCanFlattenWithStringKeyOverwriting(): void
    {
        $coll = new Collection([
            ['a' => 1],
            ['a' => 2],
        ]);

        $this->assertSame(['a' => 2], $coll->flatten()->all());
    }

    public function testCanFlattenWithNullAndScalarMixed(): void
    {
        $coll = new Collection([
            null,
            [1, 2],
            'test',
        ]);

        $this->assertSame([null, 1, 2, 'test'], $coll->flatten()->all());
    }

    public function testCanFlattenDeeplyWithCollectionConversion(): void
    {
        $coll = new Collection([
            [new Collection([1, [2, new Collection([3])]])],
        ]);

        $this->assertSame([1, 2, 3], $coll->flatten()->all());
    }

    public function testCanFlattenWithNumericKeysAndAppendOnCollision(): void
    {
        $coll = new Collection([
            [10],
            [20],
            [30, 40],
        ]);

        $this->assertSame([10, 20, 30, 40], $coll->flatten()->all());
    }

    public function testCanFlattenAtDepthOneWithAllStringKeys(): void
    {
        $coll = new Collection([
            ['a' => 1, 'b' => 2],
            ['c' => 3],
        ]);

        $this->assertSame([1, 2, 3], $coll->flatten(1)->all());
    }

    public function testCanFlattenAtDepthOneWithStringKeysPreserved(): void
    {
        $coll = new Collection([
            ['a' => 1],
            ['b' => ['c' => 2]],
        ]);

        $this->assertSame([1, ['c' => 2]], $coll->flatten(1)->all());
    }

    public function testCanFlattenWithStringKeysAtDepthOne(): void
    {
        $coll = new Collection([
            ['x' => 1, 'y' => 2],
        ]);

        $this->assertSame([1, 2], $coll->flatten(1)->all());
    }

    public function testCanFlattenStringKeysWithNumericSiblingKeys(): void
    {
        $coll = new Collection([
            ['a' => 1, 0 => 2],
            ['b' => 3],
        ]);

        $this->assertSame(['a' => 1, 2, 'b' => 3], $coll->flatten()->all());
    }

    public function testCanFlattenWithThreeLevelsAndStringKeys(): void
    {
        $coll = new Collection([
            ['a' => ['b' => ['c' => 5]]],
        ]);

        $this->assertSame([['b' => ['c' => 5]]], $coll->flatten(1)->all());
        $this->assertSame([['c' => 5]], $coll->flatten(2)->all());
    }

    public function testCanFlattenStringKeyCollisionInSameLevel(): void
    {
        $coll = new Collection([
            ['key' => 1],
            ['key' => 2],
            ['other' => 3],
        ]);

        $this->assertSame(['key' => 2, 'other' => 3], $coll->flatten()->all());
    }

    public function testCanFlattenWithCollectionAndStringKeys(): void
    {
        $coll = new Collection([
            new Collection(['x' => 1]),
            new Collection(['y' => 2]),
        ]);

        $this->assertSame(['x' => 1, 'y' => 2], $coll->flatten()->all());
    }

    public function testCanFlattenWithAllNonArrayValues(): void
    {
        $coll = new Collection([1, 2, 3, 'hello', null]);

        $this->assertSame([1, 2, 3, 'hello', null], $coll->flatten()->all());
    }

    public function testCanFlattenWithMixedDepthScenarios(): void
    {
        $coll = new Collection([
            [1, ['a' => ['b' => 2]]],
            ['c' => 3],
        ]);

        $this->assertSame([1, ['a' => ['b' => 2]], 2 => 3], $coll->flatten(1)->all());
        $this->assertSame([1, ['b' => 2], 'c' => 3], $coll->flatten(2)->all());
        $this->assertSame([1, 2, 'c' => 3], $coll->flatten(3)->all());
    }

    public function testCanFlattenCollectionAsItemWithNonArrayAfterConversion(): void
    {
        $coll = new Collection([
            new Collection([1, 2]),
        ]);

        $this->assertSame([1, 2], $coll->flatten()->all());
    }

    public function testCanFlattenWithCollectionAndDepthOne(): void
    {
        $coll = new Collection([
            new Collection(['a' => 1, 'b' => 2]),
        ]);

        $this->assertSame([1, 2], $coll->flatten(1)->all());
    }

    public function testCanFlattenWithFloatDepthOne(): void
    {
        $coll = new Collection([
            ['a' => ['b' => 1]],
        ]);

        $this->assertSame(['b' => 1], $coll->flatten(1.0)->all());
    }

    public function testCanFlattenWithFloatDepthZero(): void
    {
        $coll = new Collection([
            ['a' => ['b' => ['c' => 1]]],
        ]);

        $this->assertSame(['c' => 1], $coll->flatten(0.0)->all());
    }

    public function testCanFlattenWithFloatDepthGreaterThanOne(): void
    {
        $coll = new Collection([
            ['a' => ['b' => ['c' => 1]]],
        ]);

        $this->assertSame(['c' => 1], $coll->flatten(2.0)->all());
    }

    public function testCanFlattenCollectionWithStringKeyAndDepthOne(): void
    {
        $coll = new Collection([
            'key' => new Collection([['a'], ['b']]),
        ]);

        $this->assertSame([['a'], ['b']], $coll->flatten(1)->all());
    }

    public function testCanFlattenCollectionInstanceContainingArrays(): void
    {
        $coll = new Collection([
            new Collection([['x' => 1]]),
        ]);

        $this->assertSame([['x' => 1]], $coll->flatten(1)->all());
    }

    public function testCanFlattenNumericKeyCollisionAcrossItems(): void
    {
        $coll = new Collection([
            [0 => 1],
            [0 => 2],
        ]);

        $this->assertSame([0 => 1, 1 => 2], $coll->flatten()->all());
    }

    public function testCanFlattenEmptyArrayAsItem(): void
    {
        $coll = new Collection([[]]);

        $this->assertSame([], $coll->flatten()->all());
    }

    public function testCanFlattenObjectAsNonArrayItem(): void
    {
        $obj = new \stdClass();
        $obj->x = 1;
        $coll = new Collection([$obj]);

        $this->assertSame([$obj], $coll->flatten()->all());
    }

    public function testCanFlattenNestedCollectionWithDepth(): void
    {
        $coll = new Collection([
            new Collection([new Collection([1, 2])]),
        ]);

        $result1 = $coll->flatten(1)->all();
        $this->assertInstanceOf(Collection::class, $result1[0]);
        $this->assertSame([1, 2], $result1[0]->all());
        $this->assertSame([1, 2], $coll->flatten(2)->all());
    }

    public function testCanFlattenCollectionWithNumericKeysAtDepthOne(): void
    {
        $coll = new Collection([
            0 => ['a' => new Collection([1, 2])],
            1 => 'direct',
        ]);

        $result1 = $coll->flatten(1)->all();
        $this->assertInstanceOf(Collection::class, $result1[0]);
        $this->assertSame([1, 2], $result1[0]->all());
        $this->assertSame('direct', $result1[1]);

        $this->assertSame([1, 2, 'direct'], $coll->flatten(2)->all());
    }

    public function testCanFlattenWithDepthOneAsFloat(): void
    {
        $coll = new Collection([['a' => ['b' => 1]]]);
        $this->assertSame(['b' => 1], $coll->flatten(1.0)->all());
    }

    public function testCanFlattenWithDepthZero(): void
    {
        $coll = new Collection([[1, 2], [3, 4]]);
        $this->assertSame([1, 2, 3, 4], $coll->flatten(0)->all());
    }

    public function testCanFlattenWithDepthNegative(): void
    {
        $coll = new Collection([[1, 2]]);
        $this->assertSame([1, 2], $coll->flatten(-1)->all());
    }

    public function testCanFlattenItemIsCollection(): void
    {
        $coll = new Collection([new Collection([1, 2]), [3, 4]]);
        $this->assertSame([1, 2, 3, 4], $coll->flatten()->all());
    }

    public function testCanFlattenItemIsCollectionAtDepthOne(): void
    {
        $coll = new Collection([new Collection(['a' => 1, 'b' => 2])]);
        $this->assertSame([1, 2], $coll->flatten(1)->all());
    }

    public function testCanFlattenNumericParentKeyWithArrayCollision(): void
    {
        $coll = new Collection([
            0 => [1, 2],
            1 => [1, 3],
        ]);
        $this->assertSame([1, 2, 1, 3], $coll->flatten(1)->all());
    }

    public function testCanFlattenStringKeysFromNestedWithNumericCollisionInResult(): void
    {
        $coll = new Collection([
            ['a' => ['x' => 1]],
            ['b' => ['x' => 2]],
        ]);
        $this->assertSame(['x' => 2], $coll->flatten()->all());
    }

    public function testCanFlattenWithObjectAsNonArrayItem(): void
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $coll = new Collection([$obj]);
        $this->assertSame([$obj], $coll->flatten()->all());
    }

    public function testCanFlattenWithEmptyArrayItem(): void
    {
        $coll = new Collection([[], 1]);
        $this->assertSame([1 => 1], $coll->flatten()->all());
    }

    public function testCanFlattenDepthZeroWithCollections(): void
    {
        $coll = new Collection([new Collection([1, 2])]);
        $this->assertSame([1, 2], $coll->flatten(0)->all());
    }

    public function testCanFlattenNestedCollectionConvertedAtDifferentDepth(): void
    {
        $coll = new Collection([
            'k' => new Collection([new Collection([1])]),
        ]);
        $this->assertSame([1], $coll->flatten()->all());
    }

    public function testCanFlattenWithInfDepth(): void
    {
        $coll = new Collection([['a' => ['b' => ['c' => 1]]]]);
        $this->assertSame(['c' => 1], $coll->flatten(INF)->all());
    }

    public function testCanFlattenDepthOnePointFive(): void
    {
        $coll = new Collection([['a' => ['b' => 1]]]);
        $this->assertSame(['b' => 1], $coll->flatten(1.5)->all());
    }

    public function testCanFlattenDepthTwoPointZero(): void
    {
        $coll = new Collection([['a' => ['b' => ['c' => 1]]]]);
        $this->assertSame(['c' => 1], $coll->flatten(2.0)->all());
    }

    public function testCanFlattenWithCollectionConvertedThenFlattenedAtDepthOne(): void
    {
        $coll = new Collection([
            'x' => new Collection(['a' => 1]),
        ]);
        $this->assertSame([1], $coll->flatten(1)->all());
    }

    public function testCanFlattenMultipleItemsWhereLaterCausesNumericCollision(): void
    {
        $coll = new Collection([
            [0 => 'first'],
            [0 => 'second'],
        ]);
        $this->assertSame(['first', 'second'], $coll->flatten()->all());
    }

    public function testCanFlattenWithArrayContainingCollectionAtDepthGreaterThanOne(): void
    {
        $coll = new Collection([
            [new Collection([['a' => 5]])],
        ]);
        $this->assertSame([['a' => 5]], $coll->flatten(2)->all());
    }

    public function testCanFlattenAllScalarsAtRoot(): void
    {
        $coll = new Collection([1, 2, 'a' => 'b']);
        $this->assertSame([0 => 1, 1 => 2, 'a' => 'b'], $coll->flatten()->all());
    }

    public function testCanFlattenEmptyResultFromDeeplyNestedEmptyArrays(): void
    {
        $coll = new Collection([[[], [[]]]]);
        $this->assertSame([], $coll->flatten()->all());
    }
}
