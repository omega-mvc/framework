<?php

declare(strict_types=1);

namespace Tests\Collection;

use Omega\Collection\CollectionImmutable;
use Omega\Collection\Exceptions\ImmutableCollectionException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function array_keys;
use function array_values;
use function count;
use function in_array;
use function json_encode;
use function str_contains;

use const JSON_THROW_ON_ERROR;

#[CoversClass(CollectionImmutable::class)]
#[CoversClass(ImmutableCollectionException::class)]
final class CollectionImmutableTest extends TestCase
{
    public function testCanWorkProperlyAsAnImmutableCollection(): void
    {
        $original = [
            'buah_1' => 'mangga',
            'buah_2' => 'jeruk',
            'buah_3' => 'apel',
            'buah_4' => 'melon',
            'buah_5' => 'rambutan',
            'buah_6' => 'peer',
        ];
        $test = new CollectionImmutable($original);

        $this->assertEquals('mangga', $test->buah_1);
        $this->assertEquals('mangga', $test->get('buah_1'));
        $this->assertTrue($test->has('buah_1'));
        $this->assertTrue($test->contain('mangga'));
        $this->assertEquals(6, $test->count());

        $countIf = $test->countIf(function ($item) {
            return str_contains($item, 'e');
        });
        $this->assertEquals(4, $countIf);

        $this->assertEquals('mangga', $test->first('bukan buah'));
        $this->assertEquals('peer', $test->last('bukan buah'));

        $keys  = array_keys($original);
        $items = array_values($original);
        $this->assertEquals($keys, $test->keys());
        $this->assertEquals($items, $test->items());

        $test->each(function (string $item, string $key = '') use ($original) {
            $this->assertContains($item, $original);
            $this->assertArrayHasKey($key, $original);
        });

        $some = $test->some(function ($item) {
            return str_contains($item, 'e');
        });
        $this->assertTrue($some);

        $every = $test->every(function ($item) {
            return !str_contains($item, 'x');
        });
        $this->assertTrue($every);

        $json = json_encode($original, JSON_THROW_ON_ERROR);
        $this->assertSame($json, $test->json());
    }

    public function testCanActLikeArray(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->assertTrue(isset($coll['one']));
        $this->assertTrue(isset($coll['two']));
        $this->assertTrue(isset($coll['three']));
    }

    public function testCanDoLikeArray(): void
    {
        $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
        $coll = new CollectionImmutable($arr);

        foreach ($arr as $key => $value) {
            $this->assertEquals($value, $coll[$key]);
        }

        $this->assertTrue(isset($coll['one']));
    }

    public function testCanBeIterator(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        foreach ($coll as $key => $value) {
            $this->assertEquals($value, $coll[$key]);
        }
    }

    public function testWillThrowExceptionWhenSettingViaArrayAccess(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->expectException(ImmutableCollectionException::class);

        $coll['one'] = 4;
    }

    public function testWillThrowExceptionWhenRemovingViaArrayAccess(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->expectException(ImmutableCollectionException::class);

        unset($coll['one']);
    }

    public function testCanBeCountedUsingTheCountFunction(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->assertCount(3, $coll);
        $this->assertEquals(3, count($coll));
    }

    public function testCanRandomizeItemsInCollection(): void
    {
        $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
        $coll = new CollectionImmutable($arr);
        $item = $coll->rand();

        $this->assertTrue(in_array($item, array_values($arr)));
    }

    public function testCanGetCurrentNextPrev(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->assertEquals(1, $coll->current());
        $this->assertEquals(2, $coll->next());
        $this->assertEquals(1, $coll->prev());
    }

    public function testCanFilterUsingStrictType(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => '2', 'three' => 3]);

        $this->assertTrue($coll->contain(1));
        $this->assertFalse($coll->contain('1', true));
    }

    public function testCanGetFirstKey(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => '2', 'three' => 3]);

        $this->assertEquals('one', $coll->firstKey());
    }

    public function testCanGetFirstKeyNull(): void
    {
        $coll = new CollectionImmutable([]);

        $this->assertNull($coll->firstKey());
    }

    public function testCanGetLastKey(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => '2', 'three' => 3]);

        $this->assertEquals('three', $coll->lastKey());
    }

    public function testCanGetLastKeyNull(): void
    {
        $coll = new CollectionImmutable([]);

        $this->assertNull($coll->lastKey());
    }

    public function testCanGetFirsts(): void
    {
        $coll = new CollectionImmutable([10, 20, 30, 40, 50, 60, 70, 80, 90]);

        $this->assertEquals([10, 20], $coll->firsts(2));
    }

    public function testCanGetLasts(): void
    {
        $coll = new CollectionImmutable([10, 20, 30, 40, 50, 60, 70, 80, 90]);

        $this->assertEquals([80, 90], $coll->lasts(2));
    }

    public function testCanGetHighest(): void
    {
        $coll = new CollectionImmutable([10, 20, 30, 40, 50, 60, 70, 80, 90]);

        $this->assertEquals(90, $coll->max());

        $coll = new CollectionImmutable([
            ['rank' => 10],
            ['rank' => 50],
            ['rank' => 90],
        ]);

        $this->assertEquals(90, $coll->max('rank'));
    }

    public function testCanGetLowestValue(): void
    {
        $coll = new CollectionImmutable([10, 20, 30, 40, 50, 60, 70, 80, 90]);

        $this->assertEquals(10, $coll->min());

        $coll = new CollectionImmutable([
            ['rank' => 10],
            ['rank' => 50],
            ['rank' => 90],
        ]);

        $this->assertEquals(10, $coll->min('rank'));
    }

    public function testCanPluck(): void
    {
        $coll = [
            ['user' => 'taylor'],
            ['user' => 'nuno'],
            ['user' => 'giovannini'],
        ];
        $coll = new CollectionImmutable($coll);

        $this->assertEquals(['taylor', 'nuno', 'giovannini'], $coll->pluck('user'));
    }

    public function testCanPluckKey(): void
    {
        $coll = [
            ['id' => 1, 'user' => 'taylor'],
            ['id' => 2, 'user' => 'nuno'],
            ['id' => 3, 'user' => 'giovannini'],
        ];
        $coll = new CollectionImmutable($coll);

        $this->assertEquals([1 => 'taylor', 2 => 'nuno', 3 => 'giovannini'], $coll->pluck('user', 'id'));
    }

    public function testCanPluckFromObjects(): void
    {
        $item1 = new class {
            public string $user = 'taylor';
            public int $id     = 1;
        };
        $item2 = new class {
            public string $user = 'nuno';
            public int $id     = 2;
        };

        $coll = new CollectionImmutable([$item1, $item2]);

        $this->assertEquals(['taylor', 'nuno'], $coll->pluck('user'));
        $this->assertEquals([1 => 'taylor', 2 => 'nuno'], $coll->pluck('user', 'id'));
    }

    public function testSkipsNonArrayOrObjectItemsWhenPlucking(): void
    {
        $coll = new CollectionImmutable(['nope', 42, ['user' => 'taylor']]);

        $this->assertEquals(['taylor'], $coll->pluck('user'));
    }

    public function testCanCountByValue(): void
    {
        $coll = new CollectionImmutable(['mangga', 'mangga', 'jeruk', 'apel']);

        $this->assertEquals(['mangga' => 2, 'jeruk' => 1, 'apel' => 1], $coll->countBy());
    }

    public function testCanCountByFloatAndObjectValues(): void
    {
        $value = new class {
            public function __toString(): string
            {
                return 'x';
            }
        };

        $coll = new CollectionImmutable([1.5, 1.5, 2.5, $value, $value, ['irrelevant']]);

        $this->assertEquals(['1.5' => 2, '2.5' => 1, 'x' => 2], $coll->countBy());
    }

    public function testStopsIteratingWhenCallbackReturnsFalse(): void
    {
        $visited = [];
        $coll    = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        $coll->each(function (int $item, ?string $key = null) use (&$visited): bool {
            $visited[] = $key;

            return $item < 2;
        });

        $this->assertEquals(['one', 'two'], $visited);
    }

    public function testReturnsFalseWhenNoItemSatisfiesTheCondition(): void
    {
        $coll = new CollectionImmutable([1, 3, 5]);

        $this->assertFalse($coll->some(static fn (int $item): bool => $item % 2 === 0));
    }

    public function testReturnsFalseWhenAnItemFailsTheCondition(): void
    {
        $coll = new CollectionImmutable([2, 4, 5]);

        $this->assertFalse($coll->every(static fn (int $item): bool => $item % 2 === 0));
    }

    public function testCanCalculateTheSum(): void
    {
        $coll = new CollectionImmutable([10, 20, 30]);

        $this->assertEquals(60, $coll->sum());
    }

    public function testCanCalculateTheAverage(): void
    {
        $coll = new CollectionImmutable([10, 20, 30]);

        $this->assertEquals(20, $coll->avg());
    }

    public function testReturnsZeroWhenComputingMaxOnAnEmptyCollection(): void
    {
        $this->assertEquals(0, (new CollectionImmutable([]))->max());
    }

    public function testReturnsZeroWhenComputingMinOnAnEmptyCollection(): void
    {
        $this->assertEquals(0, (new CollectionImmutable([]))->min());
    }

    public function testCountsItemsMatchingAStrictlyTrueCondition(): void
    {
        $coll = new CollectionImmutable([1, 2, 3, 4]);

        $this->assertSame(2, $coll->countIf(static fn (int $item): bool => $item % 2 === 0));
    }

    public function testReturnsZeroWhenCountIfFindsNoMatch(): void
    {
        $coll = new CollectionImmutable([1, 3, 5]);

        $this->assertSame(0, $coll->countIf(static fn (int $item): bool => $item % 2 === 0));
    }

    public function testReturnsZeroWhenCountIfRunsOverAnEmptyCollection(): void
    {
        $this->assertSame(0, (new CollectionImmutable([]))->countIf(static fn (int $item): bool => true));
    }

    public function testPassesTheKeyToTheCountIfCondition(): void
    {
        $coll = new CollectionImmutable([1, 2, 3]);

        $this->assertSame(1, $coll->countIf(static fn (int $item, int $key = 0): bool => $key === 2));
    }

    public function testCanCountByIntegerValues(): void
    {
        $coll = new CollectionImmutable([1, 1, 2]);

        $this->assertEquals([1 => 2, 2 => 1], $coll->countBy());
    }

    public function testIgnoresNonStringableValuesWhenCountingBy(): void
    {
        $coll = new CollectionImmutable([null, true, ['x']]);

        $this->assertEquals([], $coll->countBy());
    }

    public function testCanCountByOverAnEmptyCollection(): void
    {
        $this->assertEquals([], (new CollectionImmutable([]))->countBy());
    }

    public function testCanRunEachOverAnEmptyCollection(): void
    {
        $coll = new CollectionImmutable([]);

        $this->assertSame($coll, $coll->each(static fn (int $item): bool => true));
    }

    public function testReturnsTrueWhenAnItemSatisfiesTheCondition(): void
    {
        $coll = new CollectionImmutable([1, 2, 3]);

        $this->assertTrue($coll->some(static fn (int $item): bool => $item === 2));
    }

    public function testReturnsFalseWhenSomeRunsOverAnEmptyCollection(): void
    {
        $this->assertFalse((new CollectionImmutable([]))->some(static fn (int $item): bool => true));
    }

    public function testReturnsTrueWhenEveryItemSatisfiesTheCondition(): void
    {
        $coll = new CollectionImmutable([2, 4, 6]);

        $this->assertTrue($coll->every(static fn (int $item): bool => $item % 2 === 0));
    }

    public function testReturnsTrueWhenEveryRunsOverAnEmptyCollection(): void
    {
        $this->assertTrue((new CollectionImmutable([]))->every(static fn (int $item): bool => false));
    }

    public function testReturnsZeroWhenMaxReceivesNoNumericValues(): void
    {
        $this->assertEquals(0, (new CollectionImmutable(['a', 'b']))->max());
    }

    public function testReturnsZeroWhenMinReceivesNoNumericValues(): void
    {
        $this->assertEquals(0, (new CollectionImmutable(['a', 'b']))->min());
    }

    public function testReturnsZeroWhenTheMaxKeyIsAbsent(): void
    {
        $coll = new CollectionImmutable([['a' => 1], ['a' => 2]]);

        $this->assertEquals(0, $coll->max('missing'));
    }

    public function testReturnsZeroWhenTheMinKeyIsAbsent(): void
    {
        $coll = new CollectionImmutable([['a' => 1], ['a' => 2]]);

        $this->assertEquals(0, $coll->min('missing'));
    }

    public function testCanFindTheMaxAmongNegativeValues(): void
    {
        $this->assertEquals(-2, (new CollectionImmutable([-5, -2]))->max());
    }

    public function testCanFindTheMinAmongASingleItem(): void
    {
        $this->assertEquals(7, (new CollectionImmutable([7]))->min());
    }

    public function testCanPluckOverAnEmptyCollection(): void
    {
        $this->assertEquals([], (new CollectionImmutable([]))->pluck('user'));
    }

    public function testCanPluckWithAKeyWhenSomeItemsLackIt(): void
    {
        $coll = new CollectionImmutable([['id' => 1, 'user' => 'taylor'], ['user' => 'nuno']]);

        $this->assertEquals([1 => 'taylor'], $coll->pluck('user', 'id'));
    }
}
