<?php

/**
 * Part of Omega - Tests\Macroable Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Macroable;

use Omega\Macroable\Exceptions\MacroNotFoundException;
use Omega\Macroable\MacroableTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for the MacroableTrait functionality.
 *
 * Ensures that macros can be registered, invoked, checked, and that missing macros
 * result in the appropriate exception being thrown.
 *
 * @category  Tests
 * @package   Macroable
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversTrait(MacroableTrait::class)]
final class MacroableTest extends TestCase
{
    /**
     * An anonymous mock instance using the MacroableTrait for testing.
     *
     * @var object
     */
    protected $mockClass;

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
        $this->mockClass = new class {
            use MacroableTrait;
        };
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
        $this->mockClass->resetMacro();
    }

    /**
     * Test it can add macro.
     *
     * @return void
     */
    public function testItCanAddMacro(): void
    {
        $this->mockClass->macro('test', fn (): bool => true);
        $this->mockClass->macro('test_param', fn (bool $bool): bool => $bool);

        $this->assertTrue($this->mockClass->test());
        $this->assertTrue($this->mockClass->test_param(true));
    }

    /**
     * Test it can add macro static.
     *
     * @return void
     */
    public function testItCanAddMacroStatic(): void
    {
        $this->mockClass->macro('test', fn (): bool => true);
        $this->mockClass->macro('test_param', fn (bool $bool): bool => $bool);

        $this->assertTrue($this->mockClass::test());
        $this->assertTrue($this->mockClass::test_param(true));
    }

    /**
     * Test it can check macro.
     *
     * @return void
     */
    public function itCanCheckMacro(): void
    {
        $this->mockClass->macro('test', fn (): bool => true);

        $this->assertTrue($this->mockClass->hasMacro('test'));
        $this->assertFalse($this->mockClass->hasMacro('test2'));
    }

    /**
     * Test it throw when macro is not registered.
     *
     * @return void
     */
    public function testItThrowWhenMacroIsNotRegister(): void
    {
        $this->expectException(MacroNotFoundException::class);

        $this->mockClass->test();
    }
}
