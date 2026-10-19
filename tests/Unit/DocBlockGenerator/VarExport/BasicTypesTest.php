<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function str_replace;

#[CoversClass(VarExport::class)]
final class BasicTypesTest extends TestCase
{
    public function testCompilesAPositiveIntegerCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([123]);

        $expected = <<<'PHP'
        [
            0 => 123,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesANegativeIntegerCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([-456]);

        $expected = <<<'PHP'
        [
            0 => -456,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesAZeroIntegerCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([0]);

        $expected = <<<'PHP'
        [
            0 => 0,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesAPositiveFloatCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([123.45]);

        $expected = <<<'PHP'
        [
            0 => 123.45,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesANegativeFloatCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([-67.89]);

        $expected = <<<'PHP'
        [
            0 => -67.89,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesAFloatWithADecimalPartCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([10.0]);

        $expected = <<<'PHP'
        [
            0 => 10.0,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesAWholeNumberFloatCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([10.0]);

        $expected = <<<'PHP'
        [
            0 => 10.0,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesAFloatInScientificNotationCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([1.23e4]);

        $expected = <<<'PHP'
        [
            0 => 12300.0,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesABooleanTrueCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([true]);

        $expected = <<<'PHP'
        [
            0 => true,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesABooleanFalseCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([false]);

        $expected = <<<'PHP'
        [
            0 => false,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testCompilesNullCorrectly(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([null]);

        $expected = <<<'PHP'
        [
            0 => null,
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }
}
