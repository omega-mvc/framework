<?php

/**
 * Part of Omega - Tests\Text Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Comprehensive test suite for the `Str` utility class.
 *
 * This class verifies the correctness and robustness of a wide range
 * of string-manipulation features provided by `Str`, including:
 *
 * - Character lookup, slicing, splitting, and searching.
 * - Case transformations (upper, lower, title, pascal, camel, snake, kebab).
 * - Pattern matching, replacing, masking, limiting, and slug generation.
 * - Template rendering, length evaluation, repetition, and type checks.
 * - Prefix/suffix detection and substring extraction helpers.
 *
 * Each test ensures consistent behavior across normal cases, edge cases,
 * invalid inputs, and formatting conventions, confirming the reliability
 * of the string helpers used throughout the application.
 *
 * @category  Tests
 * @package   Text
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Str::class)]
final class StrTest extends TestCase
{
    /**
     * Test it return character specified position.
     *
     * @return void
     */
    public function testItReturnCharacterSpecifiedPosition(): void
    {
        $text = 'i love laravel';
        $this->assertEquals('o', Str::charAt($text, 3));
    }

    /**
     * Test it join two or more string into once.
     *
     * @return void
     */
    public function testItJoinTwoOrMoreStringIntoOnce(): void
    {
        $text = ['i', 'love', 'laravel'];
        $this->assertEquals('i love laravel', Str::concat($text));
        $this->assertEquals('i love and laravel', Str::concat($text, ' ', 'and'));
    }

    /**
     * Test it can find index of string.
     *
     * @return void
     */
    public function testItCanFindIndexOfString(): void
    {
        $text = 'i love laravel';
        $this->assertEquals(2, Str::indexOf($text, 'l'));
    }

    /**
     * Test it can find last index of string.
     *
     * @return void
     */
    public function testItCanFindLastIndexOfString(): void
    {
        $text = 'i love laravel';
        $this->assertEquals(13, Str::lastIndexOf($text, 'l'));
    }

    /**
     * Test it can find matches from pattern.
     *
     * @return void
     */
    public function testItCanFindMatchesFromPattern(): void
    {
        $text    = 'i love laravel';
        $matches =  Str::match($text, '/love/');
        $this->assertContains('love', $matches);

        $matches = Str::match($text, '/rust/');
        $this->assertNull($matches, 'cek match return null if pattern not found');
    }

    /**
     * Test it can search text.
     *
     * @return void
     */
    public function testItCanSearchText(): void
    {
        $text = 'i love laravel';
        $this->assertEquals(7, Str::indexOf($text, 'laravel'));
        $this->assertFalse(Str::indexOf($text, 'rust'), 'the text nit contain specific string');
    }

    /**
     * Test it can slice string.
     *
     * @return void
     */
    public function testItCanSliceString(): void
    {
        $text = 'i love laravel';
        $this->assertEquals('laravel', Str::slice($text, 7), 'without length');
        $this->assertEquals('lara', Str::slice($text, 7, 4), 'without length');
        $this->assertEquals('larave', Str::slice($text, 7, -1), 'without length');
        $this->assertSame('', Str::slice($text, 15), 'out of length');
    }

    /**
     * Test it can split string.
     *
     * @return void
     */
    public function testItCanSplintString(): void
    {
        $text = 'i love laravel';
        $this->assertEquals(['i', 'love', 'laravel'], Str::split($text, ' '));
        $this->assertEquals(['i', 'love laravel'], Str::split($text, ' ', 2), 'with limit');
    }

    /**
     * Test it can find and replace text.
     *
     * @return void
     */
    public function testItCanFindAndReplaceText(): void
    {
        $text = 'i love laravel';
        $this->assertEquals('i love php', Str::replace($text, 'laravel', 'php'));
    }

    /**
     * Test it can uppercase string.
     *
     * @return void
     */
    public function testItCanUppercaseString(): void
    {
        $text = 'i love laravel';
        $this->assertEquals('I LOVE LARAVEL', Str::toUpperCase($text));
    }

    /**
     * Test it can lower case string.
     *
     * @return void
     */
    public function testItCanLowercaseString(): void
    {
        $text = 'I LOVE LARAVEL';
        $this->assertEquals('i love laravel', Str::toLowerCase($text));
    }

    /**
     * Test it can uc-first string.
     *
     * @return void
     */
    public function testItCanUcFirstString(): void
    {
        $text = 'laravel';
        $this->assertEquals('Laravel', Str::firstUpper($text));
    }

    /**
     * Test it can uc-word string.
     *
     * @return void
     */
    public function testItCanUcWordString(): void
    {
        $text = 'i love laravel';
        $this->assertEquals('I Love Laravel', Str::firstUpperAll($text));
    }

    /**
     * Test it can snake case.
     *
     * @return void
     */
    public function testItCanSnakeCase(): void
    {
        $text = 'i love laravel';
        $this->assertEquals('i_love_laravel', Str::toSnakeCase($text));

        $text = 'i-love-laravel';
        $this->assertEquals('i_love_laravel', Str::toSnakeCase($text));

        $text = 'i_love_laravel';
        $this->assertEquals('i_love_laravel', Str::toSnakeCase($text));

        $text = 'i+love+laravel';
        $this->assertEquals('i_love_laravel', Str::toSnakeCase($text));

        $text = 'i+love_laravel';
        $this->assertEquals('i_love_laravel', Str::toSnakeCase($text));
    }

    /**
     * Test it can kebab case.
     *
     * @return void
     */
    public function testItCanKebabCase(): void
    {
        $text = 'i love laravel';
        $this->assertEquals('i-love-laravel', Str::toKebabCase($text));

        $text = 'i-love-laravel';
        $this->assertEquals('i-love-laravel', Str::toKebabCase($text));

        $text = 'i_love_laravel';
        $this->assertEquals('i-love-laravel', Str::toKebabCase($text));

        $text = 'i+love+laravel';
        $this->assertEquals('i-love-laravel', Str::toKebabCase($text));

        $text = 'i+love_laravel';
        $this->assertEquals('i-love-laravel', Str::toKebabCase($text));
    }

    /**
     * Test it can pascal case.
     *
     * @return void
     */
    public function testItCanPascalCase(): void
    {
        $text = 'i love laravel';
        $this->assertEquals('ILoveLaravel', Str::toPascalCase($text));

        $text = 'i-love-laravel';
        $this->assertEquals('ILoveLaravel', Str::toPascalCase($text));

        $text = 'i_love_laravel';
        $this->assertEquals('ILoveLaravel', Str::toPascalCase($text));

        $text = 'i+love+laravel';
        $this->assertEquals('ILoveLaravel', Str::toPascalCase($text));

        $text = 'i+love_laravel';
        $this->assertEquals('ILoveLaravel', Str::toPascalCase($text));
    }

    /**
     * Test it can camel case.
     *
     * @return void
     */
    public function testItCanCamelCase(): void
    {
        $text = 'i love laravel';
        $this->assertEquals('iLoveLaravel', Str::toCamelCase($text));

        $text = 'i-love-laravel';
        $this->assertEquals('iLoveLaravel', Str::toCamelCase($text));

        $text = 'i_love_laravel';
        $this->assertEquals('iLoveLaravel', Str::toCamelCase($text));

        $text = 'i+love+laravel';
        $this->assertEquals('iLoveLaravel', Str::toCamelCase($text));

        $text = 'i+love_laravel';
        $this->assertEquals('iLoveLaravel', Str::toCamelCase($text));
    }

    /**
     * Test it can detect text contain with,
     *
     * @return void
     */
    public function testItCanDetectTextContainWith(): void
    {
        $text = 'i love laravel';
        $this->assertTrue(Str::contains($text, 'laravel'));
        $this->assertFalse(Str::contains($text, 'symfony'));
    }

    /**
     * Test it can detect text starts with.
     *
     * @return void
     */
    public function testItCanDetectTextStartsWith(): void
    {
        $text = 'i love laravel';
        $this->assertTrue(Str::startsWith($text, 'i'));
        $this->assertFalse(Str::startsWith($text, 'love'));
    }

    /**
     * Test it can detect text ends with.
     *
     * @return void
     */
    public function testItCanDetectTextEndsWith(): void
    {
        $text = 'i love laravel';
        $this->assertTrue(Str::endsWith($text, 'laravel'));
        $this->assertFalse(Str::endsWith($text, 'love'));
    }

    /**
     * Test it can make slugify from text.
     *
     * @return void
     * @throws Throwable if method slug doesnt return anything.
     */
    public function testItCanMakeSlugifyFromText(): void
    {
        $text = 'i love laravel';
        $this->assertEquals('i-love-laravel', Str::slug($text));

        $text = '-~+-';

        try {
            Str::slug($text);
        } catch (Throwable $th) {
            $this->assertEquals(
                "The method slug called with $text did not return anything.",
                $th->getMessage()
            );
        }
    }

    /**
     * Test it can render template string.
     *
     * @return void
     */
    public function testItCanRenderTemplateString(): void
    {
        $template = 'i love {lang}';
        $data     = ['lang' => 'laravel'];
        $this->assertEquals('i love laravel', Str::template($template, $data));
    }

    /**
     * Test it can count text.
     *
     * @return void
     */
    public function testItCanCountText(): void
    {
        $text = 'i love laravel';
        $this->assertEquals(14, Str::length($text));
    }

    /**
     * Test it a repeat text.
     *
     * @return void
     */
    public function testItCanRepeatText(): void
    {
        $text = 'Test';
        $this->assertEquals('TestTestTest', Str::repeat($text, 3));
    }

    /**
     * Test it can detect to string.
     *
     * @return void
     */
    public function testItCanDetectString(): void
    {
        $this->assertTrue(Str::isString('text'));
    }

    /**
     * Test it can detect empty string.
     *
     * @return void
     */
    public function testItCanDetectEmptyString(): void
    {
        $this->assertTrue(Str::isEmpty(''));
        $this->assertFalse(Str::isEmpty('test'));
    }

    /**
     * Test it can detect fill string in the start.
     *
     * @return void
     */
    public function testItCanDetectFillStringInTheStart(): void
    {
        $this->assertEquals('001212', Str::fill('1212', '0', 6));
    }

    /**
     * Test it can detect fill string in the end.
     *
     * @return void
     */
    public function testItCanDetectFillStringInTheEnd(): void
    {
        $this->assertEquals('121200', Str::fillEnd('1212', '0', 6));
    }

    /**
     * Tets it can make mask.
     *
     * @return void
     */
    public function testItCanMakeMask(): void
    {
        $this->assertEquals('l****el', Str::mask('laravel', '*', 1, 4));
        $this->assertEquals('l******', Str::mask('laravel', '*', 1));
        $this->assertEquals('lara*el', Str::mask('laravel', '*', -3, 1));
        $this->assertEquals('lara***', Str::mask('laravel', '*', -3));
    }

    /**
     * Test it can make limit.
     *
     * @return void
     */
    public function testItCanMakeLimit(): void
    {
        $this->assertEquals('laravel best...', Str::limit('laravel best framework', 12));
    }

    /**
     * Test it can get text after,
     *
     * @return void
     */
    public function testItCanGetTextAfter(): void
    {
        $this->assertEquals(
            '//localhost:8000/test',
            Str::after('https://localhost:8000/test', ':')
        );
    }

    /**
     * Test it can get text after must return back.
     *
     * @return void
     */
    public function testItCanGetTextAfterMustReturnBack(): void
    {
        $this->assertEquals(
            'https://localhost:8000/test',
            Str::after('https://localhost:8000/test', '~')
        );
    }
}
