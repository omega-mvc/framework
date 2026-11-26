<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport\Compiler;

use Closure;
use InvalidArgumentException;
use Omega\DocBlockGenerator\VarExport\Compiler\ClosureCompiler;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;
use ReflectionFunction;
use stdClass;
use Tests\TestCase;

#[CoversClass(ClosureCompiler::class)]
final class ClosureCompilerTest extends TestCase
{
    public function testCompileReturnsExtractedClosureLines(): void
    {
        $closure = function (int $a): int {
            return $a + 1;
        };

        $compiler = new ClosureCompiler();
        $lines    = $compiler->compile($closure);

        $this->assertSame([
            'function (int $a): int {',
            '    return $a + 1;',
            '}',
        ], $lines);
    }

    public function testCompileRejectsNonClosureValue(): void
    {
        $compiler = new ClosureCompiler();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a Closure instance.');

        $compiler->compile(new stdClass());
    }

    public function testGetReflectionReturnsReflectionOfCompiledClosure(): void
    {
        $closure = function (): string {
            return 'reflected';
        };

        $compiler = new ClosureCompiler();
        $compiler->compile($closure);
        $reflection = $compiler->getReflection();

        $this->assertInstanceOf(ReflectionFunction::class, $reflection);
        $this->assertSame(__FILE__, $reflection->getFileName());
        $this->assertSame($closure, $reflection->getClosure());
    }

    public function testGetCapturedVariablesReturnsStaticVariables(): void
    {
        $captured = 'outside';
        $closure  = function () use ($captured): string {
            return $captured;
        };

        $compiler = new ClosureCompiler();
        $compiler->compile($closure);

        $this->assertSame(['captured' => 'outside'], $compiler->getCapturedVariables());
    }

    public function testReflectClosureCreatesReflectionFunction(): void
    {
        $closure = function (): void {
        };

        $compiler   = new ClosureCompiler();
        $reflection = $compiler->reflectClosure($closure);

        $this->assertInstanceOf(ReflectionFunction::class, $reflection);
        $this->assertSame($closure, $reflection->getClosure());
    }

    public function testReflectClosureWrapsReflectionException(): void
    {
        $closure = function (): void {
        };

        $compiler = new class () extends ClosureCompiler {
            protected function createReflection(Closure $closure): ReflectionFunction
            {
                throw new ReflectionException('reflection failed');
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to reflect closure: reflection failed');

        $compiler->reflectClosure($closure);
    }
}
