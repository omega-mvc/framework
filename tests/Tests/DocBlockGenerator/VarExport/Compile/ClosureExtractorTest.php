<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport\Compile;

use InvalidArgumentException;
use Omega\DocBlockGenerator\VarExport\ClosureExtractor;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionFunction;
use Tests\TestCase;

#[CoversClass(ClosureExtractor::class)]
final class ClosureExtractorTest extends TestCase
{
    public function testExtractsSimpleSingleLineClosureWithoutPrefix(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = function () {
            return 'test';
        };

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $this->assertStringNotContainsString("'closure'", $result['normalized']);
        $this->assertStringNotContainsString('=>', $result['normalized']);
        $this->assertStringContainsString('function', $result['normalized']);
        $this->assertStringContainsString("return 'test'", $result['normalized']);
    }

    public function testExtractsSimpleSingleLineClosureFromArrayContext(): void
    {
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
        $this->assertStringNotContainsString("'closure'", $normalized);
        $this->assertStringNotContainsString('=>', $normalized);
        $this->assertStringContainsString('function', $normalized);
        $this->assertStringContainsString('= 1 + 2', $normalized);
        $this->assertStringContainsString('bolamasgakada', $normalized);
        $this->assertStringContainsString('echo $a', $normalized);
        $this->assertStringContainsString('$b = \'text\'', $normalized);
        $this->assertStringContainsString('return \'Route::class\'', $normalized);
    }

    public function testExtractsArrowFunctionWithoutPrefix(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = fn () => 'test';

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $this->assertStringNotContainsString("'closure'", $result['normalized']);
        $this->assertStringContainsString('fn', $result['normalized']);
        $this->assertStringContainsString("'test'", $result['normalized']);
    }

    public function testExtractsMultilineClosureStructure(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = function () {
            $x = 10;

            return $x * 2;
        };

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $normalized = $result['normalized'];
        $this->assertStringContainsString('function', $normalized);
        $this->assertStringContainsString('$x = 10', $normalized);
        $this->assertStringContainsString('return $x * 2', $normalized);
        $this->assertNotEmpty($result['lines']);
    }

    public function testLinesArrayDoesNotContainDuplicateClosureKey(): void
    {
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
        $this->assertEquals(0, $closureKeyCount);
    }

    public function testExtractsClosureWithParameters(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = function (int $a, int $b) {
            return $a + $b;
        };

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $this->assertStringContainsString('(int $a, int $b)', $result['normalized']);
        $this->assertStringContainsString('return $a + $b', $result['normalized']);
    }

    public function testExtractsClosureWithReturnType(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = function (): int {
            return 5;
        };

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $this->assertStringContainsString(': int', $result['normalized']);
        $this->assertStringContainsString('return 5', $result['normalized']);
    }

    public function testExtractsClosureWithMixedContentAndComments(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = function () {
            // This is a comment
            $result = 10;

            /* Block comment */
            return $result;
        };

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $this->assertStringContainsString('// This is a comment', $result['normalized']);
        $this->assertStringContainsString('/* Block comment */', $result['normalized']);
        $this->assertStringContainsString('$result = 10', $result['normalized']);
    }

    public function testMetadataContainsCorrectLineInformation(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = function () {
            return 'test';
        };

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $this->assertArrayHasKey('startLine', $result['metadata']);
        $this->assertArrayHasKey('endLine', $result['metadata']);
        $this->assertArrayHasKey('file', $result['metadata']);
        $this->assertArrayHasKey('isSingleLine', $result['metadata']);
        $this->assertArrayHasKey('isArrowFunction', $result['metadata']);
    }

    public function testArrowFunctionMetadataCorrectlyIdentified(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = fn () => 42;

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $this->assertTrue($result['metadata']['isArrowFunction']);
    }

    public function testRegularFunctionMetadataCorrectlyIdentified(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = function () {
            return 42;
        };

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $this->assertFalse($result['metadata']['isArrowFunction']);
    }

    public function testNormalizedCodeHasCorrectIndentationRemoved(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = function () {
            return 'test';
        };

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $firstLine = $result['lines'][0];
        $this->assertEquals(strlen($firstLine) - strlen(ltrim($firstLine)), 0);
    }

    public function testValidateSingleLineRejectsMultipleClosures(): void
    {
        $extractor = new ClosureExtractor();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiple closures detected');
        $extractor->validateSingleLine('function() {}, function() {}', 42);
    }

    public function testOriginalCodePreservedInOutput(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = function () {
            return 'test';
        };

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $this->assertArrayHasKey('original', $result);
        $this->assertStringContainsString('function', $result['original']);
    }

    public function testExtractsFromComplexArrayScenario(): void
    {
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
        $this->assertStringNotContainsString("'closure'", $normalized);
        $this->assertStringNotContainsString('=>', $normalized);
        $this->assertStringNotContainsString("'other'", $normalized);
        $this->assertStringContainsString('function', $normalized);
        $this->assertStringContainsString('= 1 + 2', $normalized);
    }

    public function testExtractsClosureWithTrailingCommaInArray(): void
    {
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
        $this->assertStringNotContainsString(',}', $normalized);
        $this->assertStringEndsWith('}', trim($normalized));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testNonExistentFileThrowsException(): void
    {
        $extractor = new ClosureExtractor();

        $reflection = $this->createMock(ReflectionFunction::class);
        $reflection->method('getFileName')->willReturn('/non/existent/file.php');
        $reflection->method('getStartLine')->willReturn(1);
        $reflection->method('getEndLine')->willReturn(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source file not found');
        $extractor->extract($reflection);
    }

    public function testAstContainsCorrectStructure(): void
    {
        $extractor = new ClosureExtractor();
        $closure   = function () {
            return 42;
        };

        $reflection = new ReflectionFunction($closure);
        $result     = $extractor->extract($reflection);

        $this->assertArrayHasKey('type', $result['ast']);
        $this->assertArrayHasKey('isArrowFunction', $result['ast']);
        $this->assertArrayHasKey('parameters', $result['ast']);
        $this->assertArrayHasKey('body', $result['ast']);
        $this->assertEquals('closure', $result['ast']['type']);
        $this->assertFalse($result['ast']['isArrowFunction']);
    }
}
