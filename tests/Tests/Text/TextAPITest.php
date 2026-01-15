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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Text\Regex;
use Omega\Text\Text;

/**
 * Test suite for validating the Text API behavior.
 *
 * This class ensures that the `Text` value object and its associated
 * transformation helpers behave consistently across all supported
 * string-manipulation features. It covers operations such as slicing,
 * casing, slug generation, pattern validation, prefix/suffix detection,
 * padding, masking, limiting, and substring lookup.
 *
 * Each test verifies correctness, immutability expectations, and
 * integration with the `Regex` helper where pattern-based checks
 * are required. The suite guarantees that the `Text` API provides
 * a reliable and predictable interface for high-level string handling.
 *
 * @category  Tests
 * @package   Text
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Regex::class)]
#[CoversClass(Text::class)]
class TextAPITest extends TestCase
{
    /**
     * Holds the Text instance under test.
     *
     * This value object represents the current string being processed,
     * and each test method operates on it to validate the behavior of the
     * `Text` API across various string transformations and queries.
     *
     * @var Text
     */
    private Text $text;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->text = new Text('i love symfony');
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->text->reset();
    }

    /**
     * Test it can return chart at.
     *
     * @return void
     */
    public function testItCanReturnChartAt(): void
    {
        $this->assertEquals('o', $this->text->charAt(3));
    }

    /**
     * Test it can return slice.
     *
     * @return void
     */
    public function testItCanReturnSlice(): void
    {
        $this->assertEquals('symfony', $this->text->slice(7));
    }

    /**
     * Test it can return lower.
     *
     * @return void
     */
    public function testItCanReturnLower(): void
    {
        $this->assertEquals('i love symfony', $this->text->lower());
    }

    /**
     * Test it can return upper.
     *
     * @return void
     */
    public function testItCanReturnUpper(): void
    {
        $this->assertEquals('I LOVE SYMFONY', $this->text->upper());
    }

    /**
     * Test it can return first upper.
     *
     * @return void
     */
    public function testItCanReturnFirstUpper(): void
    {
        $this->assertEquals('I love symfony', $this->text->firstUpper());
    }

    /**
     * Test it can return first upper all.
     *
     * @return void
     */
    public function testItCanReturnFirstUpperAll(): void
    {
        $this->assertEquals('I Love Symfony', $this->text->firstUpperAll());
    }

    /**
     * Test it can return snake.
     *
     * @return void
     */
    public function testItCanReturnSnake(): void
    {
        $this->assertEquals('i_love_symfony', $this->text->snake());
    }

    /**
     * Test it can return kebab.
     *
     * @return void
     */
    public function testItCanReturnKebab(): void
    {
        $this->assertEquals('i-love-symfony', $this->text->kebab());
    }

    /**
     * Test it can return pascal.
     *
     * @return void
     */
    public function testItCanReturnPascal(): void
    {
        $this->assertEquals('ILoveSymfony', $this->text->pascal());
    }

    /**
     * Test it can return camel.
     *
     * @return void
     */
    public function testItCanReturnCamel(): void
    {
        $this->assertEquals('iLoveSymfony', $this->text->camel());
    }

    /**
     * Test it can return slug.
     *
     * @return void
     */
    public function testItCanReturnSlug(): void
    {
        $this->assertEquals('i-love-symfony', $this->text->slug());
    }

    /**
     * Test it can return is empty.
     *
     * @return void
     */
    public function testItCanReturnIsEmpty(): void
    {
        $this->assertFalse($this->text->isEmpty());
    }

    /**
     * Test it can return is.
     *
     * @return void
     */
    public function testItCanReturnIs(): void
    {
        $this->assertFalse($this->text->is(Regex::USER));
    }

    /**
     * Test it can return contains.
     *
     * @return void
     */
    public function testItCanReturnContains(): void
    {
        $this->assertTrue($this->text->contains('love'));
    }

    /**
     * Test it can return stars with.
     *
     * @return void
     */
    public function testItCanReturnStartsWith(): void
    {
        $this->assertTrue($this->text->startsWith('i love'));
    }

    /**
     * Test it can return ends with.
     *
     * @return void
     */
    public function testItCanReturnEndsWith(): void
    {
        $this->assertTrue($this->text->endsWith('symfony'));
    }

    /**
     * test it can return length.
     *
     * @return void
     */
    public function testItCanReturnLength(): void
    {
        $this->assertIsInt($this->text->length());
        $this->assertEquals(14, $this->text->length());
    }

    /**
     * Test it can return index of.
     *
     * @return void
     */
    public function testItCanReturnIndexOf(): void
    {
        $this->assertIsInt($this->text->length());
        $this->assertEquals(7, $this->text->indexOf('symfony'));
    }

    /**
     * Test it can return index of.
     *
     * @return void
     */
    public function testItCanReturnLastIndexOf(): void
    {
        $this->assertIsInt($this->text->length());
        $this->assertEquals(3, $this->text->indexOf('o'));
    }

    /**
     * Test it can return fill.
     *
     * @return void
     */
    public function testItCanReturnFill(): void
    {
        $this->text->text('1234');
        $this->assertEquals('001234', $this->text->fill('0', 6));
    }

    /**
     * Test it can return fill end.
     *
     * @return void
     */
    public function testItCanReturnFillEnd(): void
    {
        $this->text->text('1234');
        $this->assertEquals('123400', $this->text->fillEnd('0', 6));
    }

    /**
     * Test it can return mask.
     *
     * @return void
     */
    public function testItCanReturnMask(): void
    {
        $this->text->text('laravel');
        $this->assertEquals('l****el', $this->text->mask('*', 1, 4));

        $this->text->text('laravel');
        $this->assertEquals('l******', $this->text->mask('*', 1));

        $this->text->text('laravel');
        $this->assertEquals('lara*el', $this->text->mask('*', -3, 1));

        $this->text->text('laravel');
        $this->assertEquals('lara***', $this->text->mask('*', -3));
    }

    /**
     * Test it can return limit.
     *
     * @return void
     */
    public function testItCanReturnLimit(): void
    {
        //$this->assertEquals('laravel...', $this->text->limit(7));
        $this->assertEquals('i love ...', (string) $this->text->limit(7));
    }

    /**
     * Test it can return after text.
     *
     * @return void
     */
    public function testItCanReturnAfetText(): void
    {
        $this->assertEquals('symfony', $this->text->after('love ')->__toString());
    }
}
