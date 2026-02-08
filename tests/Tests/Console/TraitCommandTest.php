<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Console;

use Omega\Console\AbstractCommand;
use Omega\Console\Style\Color\ForegroundColor;
use Omega\Console\Traits\CommandTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

use function chr;
use function ob_get_clean;
use function ob_start;
use function sprintf;

/**
 * Tests for the CommandTrait text coloring helpers.
 *
 * This test case verifies that the CommandTrait correctly formats
 * ANSI escape sequences when applying foreground colors to text.
 *
 * A lightweight anonymous command is used to expose trait methods
 * and capture their output via output buffering.
 *
 * @category  Tests
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(AbstractCommand::class)]
#[CoversClass(ForegroundColor::class)]
#[CoversTrait(CommandTrait::class)]
final class TraitCommandTest extends TestCase
{
    /**
     * Command instance used to test CommandTrait behavior.
     *
     * This is an anonymous class extending AbstractCommand and
     * using CommandTrait, created specifically to expose and
     * invoke trait methods during testing.
     *
     * @var AbstractCommand
     */
    private $command;

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
        $this->command = new class (['cli', '--test']) extends AbstractCommand {
            use CommandTrait;

            public function __call($name, $arguments)
            {
                if ($name === 'echoTextRed') {
                    echo $this->textRed('Color');
                }
                if ($name === 'echoTextYellow') {
                    echo $this->textYellow('Color');
                }
                if ($name === 'echoTextGreen') {
                    echo $this->textGreen('Color');
                }
                if ($name === 'textColor') {
                    echo $this->textColor($arguments[0], 'Color');
                }
            }
        };
    }

    /**
     * Test it can make text red.
     *
     * @return void
     */
    public function testItCanMakeTextRed(): void
    {
        ob_start();
        $this->command->echoTextRed();
        $out = ob_get_clean();

        $this->assertEquals(sprintf('%s[31mColor%s[0m', chr(27), chr(27)), $out);
    }

    /**
     * Test it can make text yellow.
     *
     * @return void
     */
    public function testItCanMakeTextYellow(): void
    {
        ob_start();
        $this->command->echoTextYellow();
        $out = ob_get_clean();

        $this->assertEquals(sprintf('%s[33mColor%s[0m', chr(27), chr(27)), $out);
    }

    /**
     * Test it can make text green.
     *
     * @return void
     */
    public function testItCanMakeTextGreen(): void
    {
        ob_start();
        $this->command->echoTextGreen();
        $out = ob_get_clean();

        $this->assertEquals(sprintf('%s[32mColor%s[0m', chr(27), chr(27)), $out);
    }

    /**
     * Test it can make text color.
     *
     * @return void
     */
    public function testItCanMakeTextColor(): void
    {
        $color = new ForegroundColor([38, 2, 0, 0, 0]);
        ob_start();
        $this->command->textColor($color);
        $out = ob_get_clean();

        $this->assertEquals(sprintf('%s[38;2;0;0;0mColor%s[0m', chr(27), chr(27)), $out);
    }
}
