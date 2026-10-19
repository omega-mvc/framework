<?php

declare(strict_types=1);

namespace Tests\Macroable;

use Closure;
use Omega\Macroable\Exceptions\MacroNotFoundException;
use Omega\Macroable\MacroableTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MacroNotFoundException::class)]
#[CoversClass(MacroableTrait::class)]

/**
 * @method bool test()
 * @method bool test_param(bool $bool)
 */
class MacroableFixture
{
    use MacroableTrait;
}


final class MacroableTest extends TestCase
{
    private MacroableFixture $mockClass;

    protected function setUp(): void
    {
        $this->mockClass = new MacroableFixture();
    }

    protected function tearDown(): void
    {
        $this->mockClass->resetMacro();
    }

    #[Test]
    public function test_can_add_macro(): void
    {
        $this->mockClass->macro('test', fn (): bool => true);
        $this->mockClass->macro('test_param', fn (bool $bool): bool => $bool);

        $this->assertTrue($this->mockClass->test());
        $this->assertTrue($this->mockClass->test_param(true));
    }

    #[Test]
    public function test_can_add_static_macro(): void
    {
        $this->mockClass->macro('test', fn (): bool => true);
        $this->mockClass->macro('test_param', fn (bool $bool): bool => $bool);

        $this->assertTrue($this->mockClass::test());
        $this->assertTrue($this->mockClass::test_param(true));
    }

    #[Test]
    public function test_can_check_macro(): void
    {
        $this->mockClass->macro('test', fn (): bool => true);

        $this->assertTrue($this->mockClass->hasMacro('test'));
        $this->assertFalse($this->mockClass->hasMacro('test2'));
    }
}
