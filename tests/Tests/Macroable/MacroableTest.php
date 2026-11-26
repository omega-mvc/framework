<?php

declare(strict_types=1);

namespace Tests\Macroable;

use Closure;
use Omega\Macroable\Exceptions\MacroNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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

    #[Test]
    public function test_can_add_a_non_closure_macro(): void
    {
        $this->mockClass->macro('strlen', 'strlen');

        $this->assertSame(5, $this->mockClass->__call('strlen', ['hello']));
        $this->assertSame(5, MacroableFixture::__callStatic('strlen', ['hello']));
    }

    #[Test]
    public function test_throws_for_an_unknown_instance_macro(): void
    {
        $this->expectException(MacroNotFoundException::class);
        $this->expectExceptionMessage('Macro `missing` is not macro able.');

        $this->mockClass->__call('missing', []);
    }

    #[Test]
    public function test_throws_for_an_unknown_static_macro(): void
    {
        $this->expectException(MacroNotFoundException::class);
        $this->expectExceptionMessage('Macro `missing` is not macro able.');

        MacroableFixture::__callStatic('missing', []);
    }

    #[Test]
    public function test_throws_when_a_static_macro_closure_cannot_be_unbound(): void
    {
        $closure = function (): mixed {
            return $this;
        };

        $this->mockClass->macro('bound', $closure);

        $this->expectException(MacroNotFoundException::class);
        $this->expectExceptionMessage('Macro `bound` is not macro able.');

        set_error_handler(static fn (): bool => true);

        try {
            MacroableFixture::__callStatic('bound', []);
        } finally {
            restore_error_handler();
        }
    }
}
