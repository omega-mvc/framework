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

use Omega\Macroable\Exceptions\MacroNotFoundException;
use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the string macro system implemented in the `Str` class.
 *
 * This test suite verifies the ability to dynamically register, invoke,
 * and reset string macros, as well as the correct handling of missing
 * macro invocations through the `MacroNotFoundException`.
 *
 * Covered scenarios include:
 * - Registering a custom macro and ensuring it behaves as expected.
 * - Throwing an exception when calling an undefined macro.
 * - Resetting all macros and validating that previously registered macros
 *   are no longer available after the reset.
 *
 * @category  Tests
 * @package   Text
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(MacroNotFoundException::class)]
#[CoversClass(Str::class)]
final class StrMacroTest extends TestCase
{
    /**
     * Test it can register string macro.
     *
     * @return void
     */
    public function testItCanRegisterStringMacro(): void
    {
        Str::macro('addPrefix', fn ($text, $prefix) => $prefix . $text);

        $this->assertEquals('i love laravel', Str::addPrefix('laravel', 'i love '));

        Str::resetMacro();
    }

    /**
     * Test it can throw error when macro not found.
     *
     * @return void
     */
    public function testItCanThrowErrorWhenMacroNotFound(): void
    {
        $this->expectException(MacroNotFoundException::class);
        Str::hay();
    }

    /**
     * Tes it can reset string macro.
     *
     * @return void
     */
    public function testItCanResetStringMacro(): void
    {
        Str::macro('addPrefix', fn ($text, $prefix) => $prefix . $text);

        $addPrefix = Str::addPrefix('a', 'b');
        $this->assertEquals('ba', $addPrefix);
        Str::resetMacro();

        $this->expectException(MacroNotFoundException::class);

        Str::addPrefix('a', 'b');
        Str::resetMacro();
    }
}
