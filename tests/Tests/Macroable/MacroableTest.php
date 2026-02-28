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
use PHPUnit\Framework\Attributes\CoversClass;
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
#[CoversClass(MacroNotFoundException::class)]
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
    public function testItCanCheckMacro(): void
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

    /**
     * Test instance macro binds this.
     *
     * @return void
     */
    public function testInstanceMacroBindsThis(): void
    {
        $this->mockClass->macro('whoAmI', function () {
            return $this;
        });

        $result = $this->mockClass->whoAmI();

        $this->assertSame($this->mockClass, $result);
    }

    /**
     * Test static macro binds to class.
     *
     * @return void
     */
    public function testStaticMacroBindsToClass(): void
    {
        $this->mockClass->macro('className', function () {
            return static::class;
        });

        $this->assertSame(
            get_class($this->mockClass),
            $this->mockClass::className()
        );
    }

    /**
     * Test non closure callable macro.
     *
     * @return void
     */
    public function testNonClosureCallableMacro(): void
    {
        $this->mockClass->macro('upper', 'strtoupper');

        $this->assertSame('CIAO', $this->mockClass->upper('ciao'));
        $this->assertSame('CIAO', $this->mockClass::upper('ciao'));
    }

    /**
     * Test macros are shared across instances.
     *
     * @return void
     */
    public function testMacrosAreSharedAcrossInstancesOfSameClass(): void
    {
        $class = new class {
            use MacroableTrait;
        };

        $class::macro('foo', fn() => 'bar');

        $instance = new $class;

        $this->assertTrue($instance::hasMacro('foo'));
    }

    /**
     * Test reset macro clears all.
     *
     * @return void
     */
    public function testResetMacroClearsAll(): void
    {
        $this->mockClass::macro('foo', fn() => true);

        $this->assertTrue($this->mockClass::hasMacro('foo'));

        $this->mockClass::resetMacro();

        $this->assertFalse($this->mockClass::hasMacro('foo'));
    }

    /**
     * Test static call throws when macro missing.
     *
     * @return void
     */
    public function testStaticCallThrowsWhenMacroMissing(): void
    {
        $this->expectException(MacroNotFoundException::class);

        $this->mockClass::missing();
    }
}
