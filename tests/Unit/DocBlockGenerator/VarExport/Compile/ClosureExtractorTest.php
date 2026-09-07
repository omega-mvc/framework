<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport\Compile;

use Omega\DocBlockGenerator\VarExport\ClosureExtractor;
use ReflectionFunction;

covers(ClosureExtractor::class);

it('extracts simple single-line closure without prefix', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = function () {
        return 'test';
    };

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    expect($result['normalized'])->not->toContain("'closure'");
    expect($result['normalized'])->not->toContain('=>');
    expect($result['normalized'])->toContain('function');
    expect($result['normalized'])->toContain("return 'test'");
});

it('extracts simple single-line closure from array context', function (): void {
    $extractor = new ClosureExtractor();

    $arrayWithClosure = [
        'closure' => function () {
            $a             = 1 + 2;
            $bolamasgakada = 1 + 2;

            echo $a;

            $b = 'text';

            // comment
            return 'Route::class';
        },
    ];

    $closure    = $arrayWithClosure['closure'];
    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    $normalized = $result['normalized'];
    expect($normalized)->not->toContain("'closure'");
    expect($normalized)->not->toContain('=>');
    expect($normalized)->toContain('function');
    expect($normalized)->toContain('= 1 + 2');
    expect($normalized)->toContain('bolamasgakada');
    expect($normalized)->toContain('echo $a');
    expect($normalized)->toContain('$b = \'text\'');
    expect($normalized)->toContain('return \'Route::class\'');
});

it('extracts arrow function without prefix', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = fn () => 'test';

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    expect($result['normalized'])->not->toContain("'closure'");
    expect($result['normalized'])->toContain('fn');
    expect($result['normalized'])->toContain("'test'");
});

it('extracts multiline closure structure', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = function () {
        $x = 10;

        return $x * 2;
    };

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    $normalized = $result['normalized'];
    expect($normalized)->toContain('function');
    expect($normalized)->toContain('$x = 10');
    expect($normalized)->toContain('return $x * 2');
    expect($result['lines'])->toBeArray();
    expect($result['lines'])->not->toBeEmpty();
});

it('lines array does not contain duplicate closure key', function (): void {
    $extractor = new ClosureExtractor();

    $arrayWithClosure = [
        'closure' => function () {
            return 42;
        },
    ];

    $closure    = $arrayWithClosure['closure'];
    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    $linesString   = implode('', $result['lines']);
    $closureKeyCount = substr_count($linesString, "'closure'");
    expect($closureKeyCount)->toEqual(0);
});

it('extracts closure with parameters', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = function ($a, $b) {
        return $a + $b;
    };

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    expect($result['normalized'])->toContain('($a, $b)');
    expect($result['normalized'])->toContain('return $a + $b');
});

it('extracts closure with return type', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = function (): int {
        return 5;
    };

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    expect($result['normalized'])->toContain(': int');
    expect($result['normalized'])->toContain('return 5');
});

it('extracts closure with mixed content and comments', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = function () {
        // This is a comment
        $result = 10;

        /* Block comment */
        return $result;
    };

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    expect($result['normalized'])->toContain('// This is a comment');
    expect($result['normalized'])->toContain('/* Block comment */');
    expect($result['normalized'])->toContain('$result = 10');
});

it('metadata contains correct line information', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = function () {
        return 'test';
    };

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    expect($result['metadata'])->toHaveKeys(['startLine', 'endLine', 'file', 'isSingleLine', 'isArrowFunction']);
    expect($result['metadata']['startLine'])->toBeInt();
    expect($result['metadata']['endLine'])->toBeInt();
    expect($result['metadata']['file'])->toBeString();
    expect($result['metadata']['isSingleLine'])->toBeBool();
    expect($result['metadata']['isArrowFunction'])->toBeBool();
});

it('arrow function metadata correctly identified', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = fn () => 42;

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    expect($result['metadata']['isArrowFunction'])->toBeTrue();
});

it('regular function metadata correctly identified', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = function () {
        return 42;
    };

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    expect($result['metadata']['isArrowFunction'])->toBeFalse();
});

it('normalized code has correct indentation removed', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = function () {
        return 'test';
    };

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    $firstLine = $result['lines'][0];
    expect(strlen($firstLine) - strlen(ltrim($firstLine)))->toEqual(0);
});

it('validate single line rejects multiple closures', function (): void {
    $extractor = new ClosureExtractor();

    expect(fn () => $extractor->validateSingleLine('function() {}, function() {}', 42))
        ->toThrow(\InvalidArgumentException::class, 'Multiple closures detected');
});

it('original code preserved in output', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = function () {
        return 'test';
    };

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    expect($result)->toHaveKey('original');
    expect($result['original'])->toBeString();
    expect($result['original'])->toContain('function');
});

it('extracts from complex array scenario', function (): void {
    $extractor = new ClosureExtractor();

    $config = [
        'name'     => 'app',
        'handlers' => [
            'closure' => function () {
                $a             = 1 + 2;
                $bolamasgakada = 1 + 2;

                echo $a;

                $b = 'text';

                // comment
                return 'Route::class';
            },
            'other' => 'value',
        ],
    ];

    $closure    = $config['handlers']['closure'];
    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    $normalized = $result['normalized'];
    expect($normalized)->not->toContain("'closure'");
    expect($normalized)->not->toContain('=>');
    expect($normalized)->not->toContain("'other'");
    expect($normalized)->toContain('function');
    expect($normalized)->toContain('= 1 + 2');
});

it('extracts closure with trailing comma in array', function (): void {
    $extractor = new ClosureExtractor();

    $arrayWithClosure = [
        'closure' => function () {
            return 'test';
        },
    ];

    $closure    = $arrayWithClosure['closure'];
    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    $normalized = $result['normalized'];
    expect($normalized)->not->toContain(',}');
    expect(trim($normalized))->toEndWith('}');
});

it('non-existent file throws exception', function (): void {
    $extractor = new ClosureExtractor();

    $reflection = $this->createMock(ReflectionFunction::class);
    $reflection->method('getFileName')->willReturn('/non/existent/file.php');
    $reflection->method('getStartLine')->willReturn(1);
    $reflection->method('getEndLine')->willReturn(1);

    expect(fn () => $extractor->extract($reflection))
        ->toThrow(\InvalidArgumentException::class, 'Source file not found');
});

it('ast contains correct structure', function (): void {
    $extractor = new ClosureExtractor();
    $closure   = function () {
        return 42;
    };

    $reflection = new ReflectionFunction($closure);
    $result     = $extractor->extract($reflection);

    expect($result['ast'])->toHaveKeys(['type', 'isArrowFunction', 'parameters', 'body']);
    expect($result['ast']['type'])->toEqual('closure');
    expect($result['ast']['isArrowFunction'])->toBeFalse();
    expect($result['ast']['parameters'])->toBeArray();
});
