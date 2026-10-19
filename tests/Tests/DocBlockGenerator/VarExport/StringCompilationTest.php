<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function str_replace;

#[CoversClass(VarExport::class)]
final class StringCompilationTest extends TestCase
{
    public function testCompilesStringWithUnicodeCharactersCorrectly(): void
    {
        $varExport = new VarExport();
        $exported  = $varExport->export(['Hello, 世界']);

        $expected = <<<'PHP'
        [
            0 => 'Hello, 世界',
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $exported));
    }

    public function testCompilesAnEmptyStringCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export(['']);

        $expected = <<<'PHP'
        [
            0 => '',
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesStringWithSingleQuotesCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export(["it's a string"]);

        $expected = <<<'PHP'
        [
            0 => 'it\'s a string',
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesStringWithDoubleQuotesCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export(['This is a "quoted" string']);

        $expected = <<<'PHP'
        [
            0 => 'This is a "quoted" string',
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesStringWithBackslashesCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export(['a\\b']);

        $expected = <<<'PHP'
        [
            0 => 'a\\b',
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesStringWithSpecialCharactersCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export(['!@#$%^&*()-=_+[]{}|;:,.<>/?']);

        $expected = <<<'PHP'
        [
            0 => '!@#$%^&*()-=_+[]{}|;:,.<>/?',
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesStringWithNewlinesCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export(["First line\nSecond line"]);

        $expected = <<<'PHP'
        [
            0 => 'First line
        Second line',
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }
}
