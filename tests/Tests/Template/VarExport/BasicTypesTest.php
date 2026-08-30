<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Template\VarExport;

/**
 * @testdox Skeleton Test for Basic Type Compilation
 */
#[CoversClass(VarExport::class)]
class BasicTypesTest extends TestCase
{
    /**
     * @test
     *
     * @testdox Compiles a positive integer correctly
     */
    public function testItCompilesPositiveInteger(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([123]);

        $expected = <<<'PHP'
[
    0 => 123,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }

    /**
     * @test
     *
     * @testdox Compiles a negative integer correctly
     */
    public function testItCompilesNegativeInteger(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([-456]);

        $expected = <<<'PHP'
[
    0 => -456,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }

    /**
     * @test
     *
     * @testdox Compiles a zero integer correctly
     */
    public function testItCompilesZeroInteger(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([0]);

        $expected = <<<'PHP'
[
    0 => 0,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }

    /**
     * @test
     *
     * @testdox Compiles a positive float correctly
     */
    public function testItCompilesPositiveFloat(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([123.45]);

        $expected = <<<'PHP'
[
    0 => 123.45,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }

    /**
     * @test
     *
     * @testdox Compiles a negative float correctly
     */
    public function testItCompilesNegativeFloat(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([-67.89]);

        $expected = <<<'PHP'
[
    0 => -67.89,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }

    /**
     * @test
     *
     * @testdox Compiles a float with a decimal part correctly
     */
    public function testItCompilesFloatWithDecimal(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([10.0]);

        $expected = <<<'PHP'
[
    0 => 10.0,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }

    /**
     * @test
     *
     * @testdox Compiles a whole number float correctly
     */
    public function testItCompilesWholeNumberFloat(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([10.0]);

        $expected = <<<'PHP'
[
    0 => 10.0,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }

    /**
     * @test
     *
     * @testdox Compiles a float in scientific notation correctly
     */
    public function testItCompilesFloatScientificNotation(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([1.23e4]);

        $expected = <<<'PHP'
[
    0 => 12300.0,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }

    /**
     * @test
     *
     * @testdox Compiles a boolean true correctly
     */
    public function testItCompilesBooleanTrue(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([true]);

        $expected = <<<'PHP'
[
    0 => true,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }

    /**
     * @test
     *
     * @testdox Compiles a boolean false correctly
     */
    public function testItCompilesBooleanFalse(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([false]);

        $expected = <<<'PHP'
[
    0 => false,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }

    /**
     * @test
     *
     * @testdox Compiles null correctly
     */
    public function testItCompilesNull(): void
    {
        $varExport = new VarExport();
        $output    = $varExport->export([null]);

        $expected = <<<'PHP'
[
    0 => null,
]
PHP;
        // Normalize line endings to LF for consistent comparison
        $normalizedOutput   = str_replace("\r\n", "\n", $output);
        $normalizedExpected = str_replace("\r\n", "\n", $expected);

        $this->assertEquals($normalizedExpected, $normalizedOutput);
    }
}
