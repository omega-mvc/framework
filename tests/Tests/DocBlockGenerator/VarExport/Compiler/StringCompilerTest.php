<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport\Compiler;

use Omega\DocBlockGenerator\VarExport\Compiler\StringCompiler;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(StringCompiler::class)]
final class StringCompilerTest extends TestCase
{
    public function testCompilesStringUsingVarExportNotation(): void
    {
        $compiler = new StringCompiler();

        $this->assertSame(["'hello world'"], $compiler->compile('hello world'));
    }

    public function testCompilesStringWithQuotesAndBackslashes(): void
    {
        $compiler = new StringCompiler();

        $this->assertSame(["'it\\'s a \\\\ path'"], $compiler->compile("it's a \\ path"));
    }

    public function testCompilesNonStringValuesUsingVarExportNotation(): void
    {
        $compiler = new StringCompiler();

        $this->assertSame(['42'], $compiler->compile(42));
        $this->assertSame(['true'], $compiler->compile(true));
        $this->assertSame(['NULL'], $compiler->compile(null));
        $this->assertSame(["array (\n  0 => 1,\n)"], $compiler->compile([1]));
    }
}
