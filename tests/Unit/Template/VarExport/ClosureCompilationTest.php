<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use Closure;
use Omega\Template\VarExport;

use function str_replace;

covers(VarExport::class);

function assertCompiles(string $expected, mixed $value): void
{
    $exporter   = new VarExport();
    $exported   = $exporter->export($value);
    $normalized = str_replace(["\r\n", "\r"], "\n", $exported);

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual($normalized);
}

it('compiles simple closure without parameters', function (): void {
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

    assertCompiles($expected, ['closure' => $closure]);
});

it('compiles PHP 7.4+ arrow function syntax (fn)', function (): void {
    $closure = fn () => 'test';

    $expected = <<<PHP
[
    'closure' => fn () => 'test',
]
PHP;

    assertCompiles($expected, ['closure' => $closure]);
});

it('compiles multi-line closure with variable assignments and return statement', function (): void {
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

    assertCompiles($expected, ['closure' => $closure]);
});

it('compiles closure with untyped parameters ($a, $b)', function (): void {
    $closure = function ($a, $b) {
        return $a + $b;
    };

    $expected = <<<'PHP'
[
    'closure' => function ($a, $b) {
        return $a + $b;
    },
]
PHP;

    assertCompiles($expected, ['closure' => $closure]);
});

it('compiles closure with typed parameters (int $a, string $b)', function (): void {
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

    assertCompiles($expected, ['closure' => $closure]);
});

it("compiles closure with default parameter values (int \$a, string \$b = 'default')", function (): void {
    $closure = function (int $a, string $b = 'default') {
        return $a . $b;
    };

    $expected = <<<'PHP'
[
    'closure' => function (int $a, string $b = 'default') {
        return $a . $b;
    },
]
PHP;

    assertCompiles($expected, ['closure' => $closure]);
});

it('compiles closure with variadic parameters (int ...$numbers)', function (): void {
    $closure = function (int ...$numbers) {
        return array_sum($numbers);
    };

    $expected = <<<'PHP'
[
    'closure' => function (int ...$numbers) {
        return array_sum($numbers);
    },
]
PHP;

    assertCompiles($expected, ['closure' => $closure]);
});

it('compiles closure with reference parameters (&$a)', function (): void {
    $closure = function (&$a) {
        $a++;
    };

    $expected = <<<'PHP'
[
    'closure' => function (&$a) {
        $a++;
    },
]
PHP;

    assertCompiles($expected, ['closure' => $closure]);
});

it('compiles closure with return type declaration (: int)', function (): void {
    $closure = function (): int {
        return 1;
    };

    $expected = <<<'PHP'
[
    'closure' => function (): int {
        return 1;
    },
]
PHP;

    assertCompiles($expected, ['closure' => $closure]);
});

it('compiles closure with nullable return type (: ?int)', function (): void {
    $closure = function (): ?int {
        return null;
    };

    $expected = <<<'PHP'
[
    'closure' => function (): ?int {
        return null;
    },
]
PHP;

    assertCompiles($expected, ['closure' => $closure]);
});

it('compiles closure with no captured variables', function (): void {
    $closure = function () {
        return 'no captured vars';
    };

    $expected = <<<'PHP'
[
    'closure' => function () {
        return 'no captured vars';
    },
]
PHP;

    assertCompiles($expected, ['closure' => $closure]);
});

it('compiles closure with one captured variable using IIFE wrapper', function (): void {
    $capturedVar = 'world';

    $closure = function () use ($capturedVar) {
        return 'hello ' . $capturedVar;
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['closure' => $closure]);

    expect($output)->toContain('(function() {');
    expect($output)->toContain('$capturedVar =');
    expect($output)->toContain('return function () use ($capturedVar)');
    expect($output)->toContain('})(),');
});

it('compiles closure with multiple captured variables using IIFE wrapper', function (): void {
    $var1 = 'hello';
    $var2 = 'world';

    $closure = function () use ($var1, $var2) {
        return $var1 . ' ' . $var2;
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['closure' => $closure]);

    expect($output)->toContain('(function() {');
    expect($output)->toContain('$var1 =');
    expect($output)->toContain('$var2 =');
    expect($output)->toContain('return function () use ($var1, $var2)');
    expect($output)->toContain('})(),');
});

it('compiles closure with captured variable by reference using IIFE wrapper', function (): void {
    $var = 'value';

    $closure = function () use (&$var) {
        $var = 'new value';
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['closure' => $closure]);

    expect($output)->toContain('(function() {');
    expect($output)->toContain('$var =');
    expect($output)->toContain('return function () use (&$var)');
    expect($output)->toContain('})(),');
});

it('compiles closure stored in variable with array context', function (): void {
    $closure = function () {
        return 42;
    };

    $config = ['handler' => $closure];

    $exporter = new VarExport();
    $output   = $exporter->export($config);

    expect($output)->toContain("'handler' => function ()");
    expect($output)->toContain('return 42');
});

it('compiles closure returned from function with proper extraction', function (): void {
    $getHandler = function () {
        return function ($value) {
            return $value * 2;
        };
    };

    $closure = $getHandler();

    $exporter = new VarExport();
    $output   = $exporter->export(['handler' => $closure]);

    expect($output)->toContain('function ($value)');
    expect($output)->toContain('return $value * 2');
});

it('compiles closure extracted from class method properly', function (): void {
    $handler = new class {
        public function getClosure(): Closure
        {
            return function ($x) {
                return $x + 1;
            };
        }
    };

    $closure = $handler->getClosure();

    $exporter = new VarExport();
    $output   = $exporter->export(['method_closure' => $closure]);

    expect($output)->toContain('function ($x)');
    expect($output)->toContain('return $x + 1');
});

it('compiles static closure', function (): void {
    $handler = new class {
        public static function getStaticClosure(): Closure
        {
            return static function () {
                return 'static closure';
            };
        }
    };

    $closure = $handler::getStaticClosure();

    $exporter = new VarExport();
    $output   = $exporter->export(['static_closure' => $closure]);

    expect($output)->toContain('function ()');
    expect($output)->toContain('static closure');
});

it('throws exception when closure comes from non-existent file', function (): void {
    $reflection = $this->createMock(\ReflectionFunction::class);
    $reflection->method('getFileName')->willReturn('/non/existent/file.php');
    $reflection->method('getStartLine')->willReturn(1);
    $reflection->method('getEndLine')->willReturn(1);

    $extractor = new VarExport\ClosureExtractor();

    expect(fn () => $extractor->extract($reflection))
        ->toThrow(\InvalidArgumentException::class, 'Source file not found');
});

it('throws exception when closure comes from unreadable file', function (): void {
    $reflection = $this->createMock(\ReflectionFunction::class);
    $reflection->method('getFileName')->willReturn('/root/restricted/file.php');
    $reflection->method('getStartLine')->willReturn(1);
    $reflection->method('getEndLine')->willReturn(1);

    $extractor = new VarExport\ClosureExtractor();

    expect(fn () => $extractor->extract($reflection))
        ->toThrow(\InvalidArgumentException::class, 'Source file not found');
});

it('compiles closure on same line with another', function (): void {
    $closure1 = function () {
        return 'first';
    };
    $closure2 = function () {
        return 'second';
    };

    $exporter = new VarExport();
    $output   = $exporter->export([
        'first'  => $closure1,
        'second' => $closure2,
    ]);

    expect($output)->toContain("'first' => function ()");
    expect($output)->toContain("'second' => function ()");
    expect($output)->toContain('first');
    expect($output)->toContain('second');
});

it('compiles nested closures extracting only outer closure', function (): void {
    $outer = function () {
        $inner = function () {
            return 'inner';
        };

        return $inner();
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['outer' => $outer]);

    expect($output)->toContain('function ()');
    expect($output)->toContain('return');
});

it('compiles closure with heredoc and nowdoc strings preserving content', function (): void {
    $closure = function () {
        $heredoc = <<<EOD
            This is a heredoc
            with multiple lines
            EOD;

        return $heredoc;
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['heredoc_closure' => $closure]);

    expect($output)->toContain('function ()');
    expect($output)->toContain('heredoc');
});

it('compiles closure in array with trailing comma properly handles syntax', function (): void {
    $closure = function () {
        return 'test';
    };

    $exporter = new VarExport();
    $output   = $exporter->export([
        'closure' => $closure,
    ]);

    expect($output)->toContain("'closure' => function ()");
    expect($output)->toContain('return \'test\'');
});

it('compiles closure with PHP 8 attributes preserving function definition', function (): void {
    $closure = function () {
        // This simulates a closure that would have attributes
        return 'closure with pseudo-attributes';
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['closure' => $closure]);

    expect($output)->toContain('function ()');
    expect($output)->toContain('return');
});

it('warns for captured variables', function (): void {
    $capturedVar = 'test_value';

    $closure = function () use ($capturedVar) {
        return $capturedVar;
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['closure' => $closure]);

    expect($output)->toContain('(function() {');
    expect($output)->toContain('})(),');
    expect($output)->toContain('use ($capturedVar)');
});

it('warning includes variable names', function (): void {
    $varOne   = 'first';
    $varTwo   = 'second';
    $varThree = 'third';

    $closure = function () use ($varOne, $varTwo, $varThree) {
        return $varOne . $varTwo . $varThree;
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['closure' => $closure]);

    expect($output)->toContain('$varOne =');
    expect($output)->toContain('$varTwo =');
    expect($output)->toContain('$varThree =');
    expect($output)->toContain('use ($varOne, $varTwo, $varThree)');
});

it('disables warning when warn captured vars is false', function (): void {
    $capturedValue = 42;

    $closure = function () use ($capturedValue) {
        return $capturedValue * 2;
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['closure' => $closure]);

    expect($output)->toContain('(function() {');
    expect($output)->toContain('$capturedValue = 42');
    expect($output)->toContain('return function () use ($capturedValue)');
    expect($output)->toContain('})(),');
});

it('uses IIFE wrapping with captured variables to preserve state', function (): void {
    $state = 'preserved';

    $closure = function () use ($state) {
        return "State is: {$state}";
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['closure' => $closure]);

    expect($output)->toContain('(function() {');
    expect($output)->toContain("'preserved'");
    expect($output)->toContain('return function');
    expect($output)->toContain('})(),');
});

it('inlines captured variable values in IIFE wrapper', function (): void {
    $num  = 123;
    $text = 'hello';
    $flag = true;

    $closure = function () use ($num, $text, $flag) {
        return compact('num', 'text', 'flag');
    };

    $exporter = new VarExport();
    $output   = $exporter->export(['closure' => $closure]);

    expect($output)->toContain('123');
    expect($output)->toContain("'hello'");
    expect($output)->toContain('true');
});
