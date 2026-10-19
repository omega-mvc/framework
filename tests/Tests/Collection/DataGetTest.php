<?php

declare(strict_types=1);

namespace Tests\Collection;

use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\TestCase;

use function Omega\Collection\data_get;
final class DataGetTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $array;

    protected function setUp(): void
    {
        $this->array = [
            'awesome'   => [
                'lang' => [
                    'go',
                    'rust',
                    'php',
                    'python',
                    'js',
                ],
            ],
            'fav'       => [
                'lang' => [
                    'rust',
                    'php',
                ],
            ],
            'dont_know' => ['lang_' => ['back_end' => ['erlang', 'h-lang']]],
            'one'       => ['two' => ['three' => ['four' => ['five' => 6]]]],
        ];
    }

    public function testCanFindItemUsingDotKeys(): void
    {
        $this->assertEquals(6, data_get($this->array, 'one.two.three.four.five'));
    }

    public function testCanFindItemUsingDotKeysButKeyDoesNotExist(): void
    {
        $this->assertEquals('six', data_get($this->array, '1.2.3.4.5', 'six'));
    }

    public function testCanFindItemsUsingDotKeyWithWildcard(): void
    {
        $this->assertEquals([
            ['go', 'rust', 'php', 'python', 'js'],
            ['rust', 'php'],
        ], data_get($this->array, '*.lang'));
    }

    public function testCanGetKeysAsInteger(): void
    {
        $array = ['foo', 'bar', 'baz'];

        $this->assertEquals('bar', data_get($array, 1));
        $this->assertNull(data_get($array, 3));
        $this->assertEquals('qux', data_get($array, 3, 'qux'));
    }

    public function testReturnsTheDefaultWhenTheKeyPathTraversesANonArrayValue(): void
    {
        $this->assertSame('missing', data_get($this->array, 'one.two.three.four.five.six', 'missing'));
    }

    public function testReturnsTheDefaultWhenAWildcardMatchesNoValues(): void
    {
        $this->assertSame('none', data_get(['a' => 1, 'b' => 2], '*.x', 'none'));
    }

    public function testReturnsTheDefaultWhenAWildcardSegmentTraversesAnEmptyArray(): void
    {
        $this->assertSame('none', data_get([], '*.x', 'none'));
        $this->assertSame('none', data_get(['a' => []], 'a.*.x', 'none'));
    }

    public function testKeepsNonNullWildcardMatchesAndDropsNullOnes(): void
    {
        $this->assertEquals([1, 3], data_get([
            'a' => ['x' => 1],
            'b' => ['y' => 2],
            'c' => ['x' => 3],
        ], '*.x'));
    }

    public function testCanFindASingleSegmentKey(): void
    {
        $this->assertEquals(['lang_' => ['back_end' => ['erlang', 'h-lang']]], data_get($this->array, 'dont_know'));
    }

    public function testReturnsTheDefaultForAMissingSingleSegmentKey(): void
    {
        $this->assertSame('fallback', data_get($this->array, 'missing', 'fallback'));
    }

    public function testCanFindATwoSegmentKey(): void
    {
        $this->assertEquals(['three' => ['four' => ['five' => 6]]], data_get($this->array, 'one.two'));
    }

    public function testReturnsTheDefaultWhenAShortPathTraversesANonArrayValue(): void
    {
        $this->assertSame('d', data_get(['a' => ['b' => 'plain']], 'a.b.c', 'd'));
    }

    public function testWildcardMatchesASingleItem(): void
    {
        $this->assertEquals(['x'], data_get(['a' => ['lang' => 'x']], '*.lang'));
    }

    public function testWildcardMatchesThreeItems(): void
    {
        $this->assertEquals(['x', 'y', 'z'], data_get([
            'a' => ['lang' => 'x'],
            'b' => ['lang' => 'y'],
            'c' => ['lang' => 'z'],
        ], '*.lang'));
    }

    public function testReturnsTheDefaultWhenAWildcardDropsEveryNullValue(): void
    {
        $this->assertSame('none', data_get(['a' => ['y' => 1]], '*.x', 'none'));
    }

    public function testWildcardWorksMidPath(): void
    {
        $this->assertEquals([1, 2], data_get(['a' => ['l1' => ['x' => 1], 'l2' => ['x' => 2]]], 'a.*.x'));
    }

    public function testReturnsTheDefaultForAnEmptyStringKey(): void
    {
        $this->assertSame('none', data_get($this->array, '', 'none'));
        $this->assertSame('none', data_get([], '', 'none'));
    }
}
