<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport\Compile;

use InvalidArgumentException;
use Omega\DocBlockGenerator\VarExport\ClosureExtractor;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionFunction;
use ReflectionMethod;
use Tests\TestCase;

use function bin2hex;
use function chmod;
use function file_put_contents;
use function random_bytes;
use function restore_error_handler;
use function set_error_handler;
use function stream_wrapper_register;
use function stream_wrapper_unregister;
use function sys_get_temp_dir;
use function tempnam;
use function token_get_all;
use function unlink;

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

    #[AllowMockObjectsWithoutExpectations]
    public function testValidateReflectionRejectsRuntimeCreatedClosure(): void
    {
        $extractor = new ClosureExtractor();

        $reflection = $this->createMock(ReflectionFunction::class);
        $reflection->method('getFileName')->willReturn(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot extract runtime-created closure');
        $extractor->extract($reflection);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testValidateReflectionRejectsClosureWithUnknownLines(): void
    {
        $extractor = new ClosureExtractor();

        $reflection = $this->createMock(ReflectionFunction::class);
        $reflection->method('getFileName')->willReturn(__FILE__);
        $reflection->method('getStartLine')->willReturn(false);
        $reflection->method('getEndLine')->willReturn(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot extract closure with unknown start or end line.');
        $extractor->extract($reflection);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testValidateReflectionRejectsUnreadableSourceFile(): void
    {
        $extractor = new ClosureExtractor();

        $file = tempnam(sys_get_temp_dir(), 'ce');
        $this->assertIsString($file);
        chmod($file, 0000);

        $reflection = $this->createMock(ReflectionFunction::class);
        $reflection->method('getFileName')->willReturn($file);
        $reflection->method('getStartLine')->willReturn(1);
        $reflection->method('getEndLine')->willReturn(1);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Source file not readable');
            $extractor->extract($reflection);
        } finally {
            chmod($file, 0600);
            unlink($file);
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testReadSourceLinesThrowsWhenFileCannotBeRead(): void
    {
        $extractor = new ClosureExtractor();

        $wrapper = new class {
            /** @param mixed $openedPath */
            public function stream_open(string $path, string $mode, int $options, mixed &$openedPath): bool
            {
                return false;
            }

            /** @return array<string, int> */
            public function url_stat(string $path, int $flags): array
            {
                return [
                    'dev' => 0,
                    'ino' => 0,
                    'mode' => 0444,
                    'nlink' => 1,
                    'uid' => 0,
                    'gid' => 0,
                    'size' => 0,
                    'atime' => 0,
                    'mtime' => 0,
                    'ctime' => 0,
                    'blksize' => 0,
                    'blocks' => 0,
                ];
            }
        };

        $scheme = 'closurefail-' . bin2hex(random_bytes(4));
        $this->assertTrue(stream_wrapper_register($scheme, $wrapper::class));

        $reflection = $this->createMock(ReflectionFunction::class);
        $reflection->method('getFileName')->willReturn($scheme . '://source.php');
        $reflection->method('getStartLine')->willReturn(1);
        $reflection->method('getEndLine')->willReturn(1);

        set_error_handler(static fn (): bool => true);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Cannot read file');
            $extractor->extract($reflection);
        } finally {
            restore_error_handler();
            stream_wrapper_unregister($scheme);
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testExtractThrowsWhenClosureCannotBeLocated(): void
    {
        $extractor = new ClosureExtractor();

        $file = tempnam(sys_get_temp_dir(), 'ce');
        $this->assertIsString($file);
        file_put_contents($file, "<?php\n\$value = 1;\n");

        $reflection = $this->createMock(ReflectionFunction::class);
        $reflection->method('getFileName')->willReturn($file);
        $reflection->method('getStartLine')->willReturn(2);
        $reflection->method('getEndLine')->willReturn(2);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Could not extract closure');
            $extractor->extract($reflection);
        } finally {
            unlink($file);
        }
    }

    public function testBuildClosureAstHandlesUseClause(): void
    {
        $extractor = new ClosureExtractor();
        $captured  = 'outer';
        $closure   = function () use ($captured) {
            return $captured;
        };

        $result = $extractor->extract(new ReflectionFunction($closure));

        $this->assertSame('closure', $result['ast']['type']);
        $this->assertStringContainsString('use ($captured)', $result['normalized']);
    }

    public function testNormalizeClosureCodeStripsTrailingCommaAndEmptyLines(): void
    {
        $extractor = new ClosureExtractor();
        $normalize = new ReflectionMethod($extractor, 'normalizeClosureCode');

        $this->assertSame('fn () => [1, 2]', $normalize->invoke($extractor, "fn () => [1, 2],\n\n"));
    }

    public function testNormalizeClosureCodeReturnsEmptyForBlankInput(): void
    {
        $extractor = new ClosureExtractor();
        $normalize = new ReflectionMethod($extractor, 'normalizeClosureCode');

        $this->assertSame('', $normalize->invoke($extractor, "\n \n\t\n"));
    }

    public function testFindMinimumIndentationIgnoresBlankLines(): void
    {
        $extractor = new ClosureExtractor();
        $indent    = new ReflectionMethod($extractor, 'findMinimumIndentation');

        $this->assertSame(0, $indent->invoke($extractor, ['']));
    }

    public function testBuildClosureAstParsesParametersReturnTypeAndBody(): void
    {
        $extractor = new ClosureExtractor();
        $buildAst  = new ReflectionMethod($extractor, 'buildClosureAST');
        $tokens    = token_get_all('<?php function (int $a, int $b): int { return $a + $b; }');

        $this->assertSame([
            'type'            => 'closure',
            'isArrowFunction' => false,
            'parameters'      => ['int $a, int $b'],
            'body'            => '{ return $a + $b; }',
            'returnType'      => 'int',
        ], $buildAst->invoke($extractor, $tokens));
    }

    public function testBuildClosureAstParsesArrowFunction(): void
    {
        $extractor = new ClosureExtractor();
        $buildAst  = new ReflectionMethod($extractor, 'buildClosureAST');
        $tokens    = token_get_all('<?php fn (int $a): int => $a + 1');

        $this->assertSame([
            'type'            => 'closure',
            'isArrowFunction' => true,
            'parameters'      => ['int $a'],
            'body'            => '=> $a + 1',
            'returnType'      => 'int',
        ], $buildAst->invoke($extractor, $tokens));
    }

    public function testExtractFromSourceCodeParsesRegularFunctionWithNestedBraces(): void
    {
        $extractor = new ClosureExtractor();
        $extract   = new ReflectionMethod($extractor, 'extractFromSourceCode');
        $code      = 'function () { if (true) { return 1; } return 2; }';

        $this->assertSame($code, $extract->invoke($extractor, $code, 'source.php', 1));
    }

    public function testExtractFromSourceCodeParsesRegularFunctionWithBracesInString(): void
    {
        $extractor = new ClosureExtractor();
        $extract   = new ReflectionMethod($extractor, 'extractFromSourceCode');
        $code      = "function () { \$s = '}'; return \$s; }";

        $this->assertSame($code, $extract->invoke($extractor, $code, 'source.php', 1));
    }

    public function testExtractFromSourceCodeParsesArrowFunctionWithDelimiter(): void
    {
        $extractor = new ClosureExtractor();
        $extract   = new ReflectionMethod($extractor, 'extractFromSourceCode');
        $code      = "'key' => fn (\$x) => \$x * 2,";

        $this->assertSame('fn ($x) => $x * 2', $extract->invoke($extractor, $code, 'source.php', 1));
    }

    public function testExtractFromSourceCodeThrowsForArrowWithoutDoubleArrow(): void
    {
        $extractor = new ClosureExtractor();
        $extract   = new ReflectionMethod($extractor, 'extractFromSourceCode');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not extract closure from line 7 in source.php');

        $extract->invoke($extractor, 'fn ()', 'source.php', 7);
    }

    public function testExtractFromSourceCodeThrowsForArrowWithoutDelimiter(): void
    {
        $extractor = new ClosureExtractor();
        $extract   = new ReflectionMethod($extractor, 'extractFromSourceCode');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not extract closure from line 8 in source.php');

        $extract->invoke($extractor, 'fn () => 1', 'source.php', 8);
    }

    public function testExtractFromSourceCodeThrowsForUnclosedRegularFunction(): void
    {
        $extractor = new ClosureExtractor();
        $extract   = new ReflectionMethod($extractor, 'extractFromSourceCode');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not extract closure from line 9 in source.php');

        $extract->invoke($extractor, 'function () { return 1;', 'source.php', 9);
    }

    public function testExtractFromSourceCodeIgnoresBracesAfterClosureEnd(): void
    {
        $extractor = new ClosureExtractor();
        $extract   = new ReflectionMethod($extractor, 'extractFromSourceCode');
        $code      = 'function () { return 1; } }';

        $this->assertSame('function () { return 1; }', $extract->invoke($extractor, $code, 'source.php', 1));
    }
}
