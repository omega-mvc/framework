<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Closure;
use InvalidArgumentException;
use Omega\DocBlockGenerator\VarExport;
use Omega\DocBlockGenerator\VarExport\ClosureExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionFunction;
use Tests\TestCase;

use function str_replace;

#[CoversClass(VarExport::class)]
final class ClosureCompilationTest extends TestCase
{
    /**
     * @param array<mixed> $value
     */
    private function assertCompiles(string $expected, array $value): void
    {
        $exporter   = new VarExport();
        $exported   = $exporter->export($value);
        $normalized = str_replace(["\r\n", "\r"], "\n", $exported);

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), $normalized);
    }

    public function testCompilesSimpleClosureWithoutParameters(): void
    {
        $closure = function () {
            return 'test';
        };

        $expected = <<<'PHP'
        [
            'closure' => function () {
                return 'test';
            },
        ]
        PHP;

        $this->assertCompiles($expected, ['closure' => $closure]);
    }

    public function testCompilesPhp74ArrowFunctionSyntaxFn(): void
    {
        $closure = fn () => 'test';

        $expected = <<<PHP
        [
            'closure' => fn () => 'test',
        ]
        PHP;

        $this->assertCompiles($expected, ['closure' => $closure]);
    }

    public function testCompilesMultiLineClosureWithVariableAssignmentsAndReturnStatement(): void
    {
        $closure = function () {
            $a = 1;
            $b = 2;

            return $a + $b;
        };

        $expected = <<<'PHP'
        [
            'closure' => function () {
                $a = 1;
                $b = 2;

                return $a + $b;
            },
        ]
        PHP;

        $this->assertCompiles($expected, ['closure' => $closure]);
    }

    public function testCompilesClosureWithParametersAAndB(): void
    {
        $closure = function (int $a, int $b) {
            return $a + $b;
        };

        $expected = <<<'PHP'
        [
            'closure' => function (int $a, int $b) {
                return $a + $b;
            },
        ]
        PHP;

        $this->assertCompiles($expected, ['closure' => $closure]);
    }

    public function testCompilesClosureWithTypedParametersIntAAndStringB(): void
    {
        $closure = function (int $a, string $b) {
            return $a . $b;
        };

        $expected = <<<'PHP'
        [
            'closure' => function (int $a, string $b) {
                return $a . $b;
            },
        ]
        PHP;

        $this->assertCompiles($expected, ['closure' => $closure]);
    }
}
