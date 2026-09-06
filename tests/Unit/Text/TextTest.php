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
use Omega\Text\Text;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function Omega\Text\string;
use function Omega\Text\text;

/**
 * Test suite for validating the core behavior of the Text value object.
 *
 * This class ensures that Text instances can be created through helper
 * functions, via the Str facade, and directly. It verifies correct text
 * retrieval, string casting, mutation without reset, logging of operations,
 * state resetting and refreshing, and the ability to chain transformations
 * while preserving workflow continuity. Together, these tests confirm that
 * the Text API behaves consistently as a fluent, traceable, and predictable
 * string manipulation utility.
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
#[CoversClass(Text::class)]
final class TextTest extends TestCase
{
    /**
     * Test it can create new instance using helper.
     *
     * @return void
     */
    public function testItCanCreateNewInstanceUsingHelper(): void
    {
        $this->assertInstanceOf(Text::class, string('text'));
        $this->assertInstanceOf(Text::class, text('text'));
    }

    /**
     * Test it can create new instance using str class.
     *
     * @return void
     */
    public function testItCanCreateNewInstanceUsingSTRClass(): void
    {
        $this->assertInstanceOf(Text::class, Str::of('text'));
    }

    /**
     * Test it can set get current text.
     *
     * @return void
     */
    public function testItCanSetGetCurrentText(): void
    {
        $class = new Text('text');

        $this->assertEquals('text', $class->getText());
    }

    /**
     * Test it can set get current text using to string.
     *
     * @return void
     */
    public function testItCanSetGetCurrentTextUsingToString(): void
    {
        $class = new Text('text');

        $this->assertEquals('text', $class);
    }

    /**
     * Test it can set new text without reset.
     *
     * @return void
     */
    public function testItCanSetNewTextWithoutReset(): void
    {
        $class = new Text('text');
        $class->upper()->lower()->firstUpper();
        $class->text('string');

        $this->assertEquals('string', $class->getText());
        $this->assertCount(5, $class->logs());
    }

    /**
     * Test it can set get log of string.
     *
     * @return void
     */
    public function testItCanSetGetLogOfString(): void
    {
        $class = new Text('text');
        $class->upper()->lower()->firstUpper();

        $this->assertIsArray($class->logs());
        foreach ($class->logs() as $log) {
            $this->assertArrayHasKey('function', $log);
            $this->assertArrayHasKey('return', $log);
            $this->assertArrayHasKey('type', $log);

            if ($log['type'] === 'string') {
                $this->assertIsString($log['return']);
            }
        }
    }

    /**
     * Test it can set reset.
     *
     * @return void
     */
    public function testItCanSetReset(): void
    {
        $class = new Text('text');
        $class->upper()->lower()->firstUpper();
        $class->reset();

        $this->assertEquals('text', $class->getText());
        $this->assertEmpty($class->logs());
    }

    /**
     * Test it can set refresh.
     *
     * @return void
     */
    public function testItCanSetRefresh(): void
    {
        $class = new Text('text');
        $class->upper()->lower()->firstUpper();
        $class->refresh('string');

        $this->assertEquals('string', $class->getText());
        $this->assertEmpty($class->logs());
    }

    /**
     * Test it can chain non string and continue chain without break.
     *
     * @return void
     */
    public function testItCanChainNonStringAndContinueChainWithoutBreak(): void
    {
        $class = new Text('text');
        $class->upper()->firstUpper();

        $this->assertTrue($class->startsWith('T'));
        $this->assertTrue($class->length() === 4);

        $class->lower();
        $this->assertTrue($class->startsWith('t'));
    }
}
