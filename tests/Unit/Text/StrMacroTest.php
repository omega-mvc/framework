<?php

declare(strict_types=1);

namespace Tests\Text;

use Omega\Macroable\Exceptions\MacroNotFoundException;
use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(MacroNotFoundException::class)]
#[CoversClass(Str::class)]
final class StrMacroTest extends TestCase
{
    public function testCanRegisterStringMacro(): void
    {
        Str::macro('addPrefix', fn (string $text, string $prefix): string => $prefix . $text);

        $this->assertSame('i love laravel', Str::addPrefix('laravel', 'i love '));

        Str::resetMacro();
    }

    public function testCanThrowErrorWhenMacroNotFound(): void
    {
        $this->expectException(MacroNotFoundException::class);

        Str::hay();
    }

    public function testCanResetStringMacro(): void
    {
        Str::macro('addPrefix', fn (string $text, string $prefix): string => $prefix . $text);

        $addPrefix = Str::addPrefix('a', 'b');
        $this->assertSame('ba', $addPrefix);
        Str::resetMacro();

        $this->expectException(MacroNotFoundException::class);

        Str::addPrefix('a', 'b');

        Str::resetMacro();
    }
}
