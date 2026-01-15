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

declare(strict_types=1);

namespace Tests\Collection;

use Omega\Collection\CollectionImmutable;
use Omega\Collection\Exceptions\ImmutableCollectionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_values;
use function count;
use function in_array;
use function json_encode;
use function str_contains;

/**
 * Class CollectionImmutableTest
 *
 * This test suite verifies the functionality, behavior, and immutability
 * of the CollectionImmutable class from the Omega\Collection package.
 *
 * The CollectionImmutable class provides a collection implementation that:
 *  - Is immutable: attempts to modify items via array access or removal throw exceptions.
 *  - Supports iteration, key/value access, and common collection operations.
 *  - Provides functional helpers such as `each`, `some`, `every`, `pluck`, `first`, `last`, `max`, and `min`.
 *  - Can be treated like a native array for read-only operations.
 *  - Handles both associative and indexed collections gracefully.
 *
 * This test suite includes:
 *  - Verification of immutability enforcement via exceptions on set/unset operations.
 *  - Validation of collection access methods including get, keys, items, firstKey, lastKey, firsts, lasts,
 *    max, min, and pluck.
 *  - Checks for iteration behavior and array-like access.
 *  - Tests for functional helpers such as `each`, `some`, `every`, `contain`, `countIf`, and random element retrieval.
 *  - Ensures JSON serialization reflects the underlying collection correctly.
 *  - Confirms proper handling of strict type comparisons where applicable.
 *
 * The goal is to guarantee that CollectionImmutable behaves predictably and consistently
 * across a wide variety of scenarios, preserving immutability while providing powerful
 * read-only collection utilities.
 *
 * @category  Tests
 * @package   Collection
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(CollectionImmutable::class)]
#[CoversClass(ImmutableCollectionException::class)]
class CollectionImmutableTest extends TestCase
{
    /**
     * Test it collection immutable functional work properly.
     *
     * @return void
     */
    public function testItCollectionImmutableFunctionalWorKProperly(): void
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

        // getter
        $this->assertEquals('mangga', $test->buah_1, 'add new item collection using __set');
        $this->assertEquals('mangga', $test->get('buah_1'), 'add new item collection using set()');

        // cek array key
        $this->assertTrue($test->has('buah_1'), 'collection have item with key');

        // cek contain
        $this->assertTrue($test->contain('mangga'), 'collection have item');

        // count
        $this->assertEquals(6, $test->count(), 'count item in collection');

        // count by
        $countIf = $test->countIf(function ($item) {
            // find letter contain 'e' letter
            return str_contains($item, 'e');
        });
        $this->assertEquals(4, $countIf, 'count item in collection with some condition');

        // first and last item cek
        $this->assertEquals('mangga', $test->first('bukan buah'), 'get first item in collection');
        $this->assertEquals('peer', $test->last('bukan buah'), 'get last item in collection');

        // test array keys and values
        $keys  = array_keys($original);
        $items = array_values($original);
        $this->assertEquals($keys, $test->keys(), 'get all key in collection');
        $this->assertEquals($items, $test->items(), 'get all item value in collection');

        // each function
        $test->each(function ($item, $key) use ($original) {
            $this->assertTrue(in_array($item, $original), 'test each with value');
            $this->assertArrayHasKey($key, $original, 'test each with key');
        });

        // test the collection have item with e letter
        $some = $test->some(function ($item) {
            // find letter contain 'e' letter
            return str_contains($item, 'e');
        });
        $this->assertTrue($some, 'test the collection have item with "e" letter');

        // test the collection every item don't have 'x' letter
        $every = $test->every(function ($item) {
            // find letter contain 'x' letter
            return !str_contains($item, 'x');
        });
        $this->assertTrue($every, 'collection every item dont have "x" letter');

        // json output
        $json = json_encode($original);
        $this->assertJsonStringEqualsJsonString($test->json(), $json, 'collection convert to json string');
    }

    /**
     * Test it can act like array.
     *
     * @return void
     */
    public function testItCanActingLikeArray(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

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
        $coll = new CollectionImmutable($arr);

        // get
        foreach ($arr as $key => $value) {
            $this->assertEquals($value, $coll[$key]);
        }

        // has
        $this->assertTrue(isset($coll['one']));
    }

    /**
     * Test it can be iterator.
     *
     * @return void
     */
    public function testItCanBeIterator(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        foreach ($coll as $key => $value) {
            $this->assertEquals($value, $coll[$key]);
        }
    }

    /**
     * Test it will throw exception with set method.
     *
     * @return void
     */
    public function testItWillThrowExceptionWithSetMethod(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->expectException(ImmutableCollectionException::class);
        $coll['one'] = 4;
    }

    /**
     * Test it will throw exception with remove method.
     *
     * @return void
     */
    public function testItWillThrowExceptionWithRemoveMethod(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->expectException(ImmutableCollectionException::class);
        unset($coll['one']);
    }

    /**
     * Test it can count using count function.
     *
     * @return void
     */
    public function testItCanCountUsingCountFunction(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->assertCount(3, $coll);
        $this->assertEquals(3, count($coll));
    }

    /**
     * Test it can randomize items in collection.
     *
     * @return void
     */
    public function testItCanRandomizeItemsInCollection(): void
    {
        $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
        $coll = new CollectionImmutable($arr);
        $item = $coll->rand();

        $this->assertTrue(
            in_array($item, array_values($arr))
        );
    }

    /**
     * Test it can get current next prev.
     *
     * @return void
     */
    public function testItCanGetCurrentNextPrev(): void
    {
        $arr  = ['one' => 1, 'two' => 2, 'three' => 3];
        $coll = new CollectionImmutable($arr);

        $this->assertEquals(1, $coll->current());
        $this->assertEquals(2, $coll->next());
        $this->assertEquals(1, $coll->prev());
    }

    /**
     * Test it can filter using strict type.
     *
     * @return void
     */
    public function testItCanFilterUsingStrictType(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => '2', 'three' => 3]);

        $this->assertTrue(
            $coll->contain(1)
        );
        $this->assertFalse(
            $coll->contain('1', true)
        );
    }

    /**
     * Test it can get first key.
     *
     * @return void
     */
    public function testItCanGetFirstKey(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => '2', 'three' => 3]);

        $this->assertEquals('one', $coll->firstKey());
    }

    /**
     * Test it can get first key null.
     *
     * @return void
     */
    public function testItCanGetFirstKeyNull(): void
    {
        $coll = new CollectionImmutable([]);

        $this->assertEquals(null, $coll->firstKey());
    }

    /**
     * Test it can get last key.
     *
     * @return void
     */
    public function testItCanGetLastKey(): void
    {
        $coll = new CollectionImmutable(['one' => 1, 'two' => '2', 'three' => 3]);

        $this->assertEquals('three', $coll->lastKey());
    }

    /**
     * Test it can get last key null.
     *
     * @return void
     */
    public function testItCanGetLastKeyNull(): void
    {
        $coll = new CollectionImmutable([]);

        $this->assertEquals(null, $coll->lastKey());
    }

    /**
     * Test it can get first.
     *
     * @return void
     */
    public function testItCanGetFirst(): void
    {
        $coll = new CollectionImmutable([10, 20, 30, 40, 50, 60, 70, 80, 90]);

        $this->assertEquals([10, 20], $coll->firsts(2));
    }

    /**
     * Test it can get lasts.
     *
     * @return void
     */
    public function testItCanGetLasts(): void
    {
        $coll = new CollectionImmutable([10, 20, 30, 40, 50, 60, 70, 80, 90]);

        $this->assertEquals([80, 90], $coll->lasts(2));
    }

    /**
     * Test it can get highest.
     *
     * @return void
     */
    public function testItCanGetHighest(): void
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

    /**
     * Test it can get the lowest value.
     *
     * @return void
     */
    public function testItCanGetLowestValue(): void
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

    /**
     * Test it can pluck.
     *
     * @return void
     */
    public function testItCanPluck(): void
    {
        $coll = [
            ['user' => 'taylor'],
            ['user' => 'nuno'],
            ['user' => 'giovannini'],
        ];
        $coll = new CollectionImmutable($coll);

        $this->assertEquals(['taylor', 'nuno', 'giovannini'], $coll->pluck('user'));
    }

    /**
     * Test it can pluck key.
     *
     * @return void
     */
    public function testItCanPluckKey(): void
    {
        $coll = [
            ['id' => 1, 'user' => 'taylor'],
            ['id' => 2, 'user' => 'nuno'],
            ['id' => 3, 'user' => 'giovannini'],
        ];
        $coll = new CollectionImmutable($coll);

        $this->assertEquals([1 => 'taylor', 2 => 'nuno', 3 => 'giovannini'], $coll->pluck('user', 'id'));
    }
}
