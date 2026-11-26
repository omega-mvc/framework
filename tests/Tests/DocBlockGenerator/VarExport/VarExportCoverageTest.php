<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\DocBlockGenerator\Fixtures\ObjectWithSetState;
use Tests\TestCase;

use function fclose;
use function fopen;
use function str_repeat;
use function str_replace;
use function trim;

use const PHP_EOL;

#[CoversClass(VarExport::class)]
final class VarExportCoverageTest extends TestCase
{
    private function normalize(string $value): string
    {
        return str_replace(["\r\n", "\r"], "\n", $value);
    }

    /**
     * @param array<mixed> $arguments
     */
    private function invokePrivate(VarExport $exporter, string $method, array $arguments = []): mixed
    {
        return (new ReflectionClass($exporter))->getMethod($method)->invokeArgs($exporter, $arguments);
    }

    public function testSetIndentationLevelIncreasesNestedIndentation(): void
    {
        $exporter = (new VarExport())->setIndentationLevel(2);

        $output = $this->normalize($exporter->export(['a' => ['b' => 1]]));

        $this->assertStringContainsString(str_repeat('    ', 3) . "'a' =>", $output);
        $this->assertStringContainsString(str_repeat('    ', 4) . "'b' => 1,", $output);
    }

    public function testSetAlignArrayAlignsKeys(): void
    {
        $exporter = (new VarExport())->setAlignArray();

        $output = $this->normalize($exporter->export(['a' => 1, 'longer' => 2]));

        $this->assertSame(
            $this->normalize(<<<'PHP'
            [
                'a'     => 1,
                'longer' => 2,
            ]
            PHP),
            $output
        );
    }

    public function testSetAlignArrayCanBeDisabled(): void
    {
        $exporter = (new VarExport())->setAlignArray(false);

        $output = $this->normalize($exporter->export(['a' => 1, 'longer' => 2]));

        $this->assertSame(
            $this->normalize(<<<'PHP'
            [
                'a' => 1,
                'longer' => 2,
            ]
            PHP),
            $output
        );
    }

    public function testCompileFallbackHandlesClosedResource(): void
    {
        $resource = fopen('php://memory', 'r');
        if (false === $resource) {
            $this->fail('Unable to open memory stream.');
        }
        fclose($resource);

        $output = $this->normalize((new VarExport())->export([$resource]));

        $this->assertSame(
            $this->normalize(<<<'PHP'
            [
                0 => NULL,
            ]
            PHP),
            $output
        );
    }

    public function testGetLastBufferReturnsNullForEmptyBufferAndLastValue(): void
    {
        $exporter = new VarExport();

        $this->assertNull($this->invokePrivate($exporter, 'getLastBuffer'));

        $this->invokePrivate($exporter, 'addToBuffer', ['first']);
        $this->invokePrivate($exporter, 'addToBuffer', ['second']);

        $this->assertSame('second', $this->invokePrivate($exporter, 'getLastBuffer'));
    }

    /**
     * @return iterable<string, array{array<int, string>}>
     */
    public static function provideNamespaceLists(): iterable
    {
        yield 'empty' => [[]];
        yield 'one empty' => [['']];
        yield 'two empty' => [['', '']];
        yield 'one namespace' => [['App\\Beta']];
        yield 'namespace then empty' => [['App\\Beta', '']];
        yield 'empty then namespace' => [['', 'App\\Beta']];
        yield 'two namespaces' => [['Zed\\Alpha', 'App\\Beta']];
        yield 'namespace empty namespace' => [['Zed\\Alpha', '', 'App\\Beta']];
        yield 'two namespaces then empty' => [['Zed\\Alpha', 'App\\Beta', '']];
        yield 'namespace then two empty' => [['Zed\\Alpha', '', '']];
        yield 'empty then two namespaces' => [['', 'Zed\\Alpha', 'App\\Beta']];
        yield 'four mixed' => [['Zed\\Alpha', '', 'App\\Beta', '']];
    }

    /**
     * @param array<int, string> $namespaces
     */
    #[DataProvider('provideNamespaceLists')]
    public function testCompileNamespacePathVariants(array $namespaces): void
    {
        $result = $this->invokePrivate(new VarExport(), 'compileNamespace', [$namespaces]);

        $this->assertIsArray($result);
    }

    public function testCompileNamespaceSortsFiltersAndAppendsNewlines(): void
    {
        $result = $this->invokePrivate(new VarExport(), 'compileNamespace', [['Zed\\Alpha', '', 'App\\Beta']]);

        $this->assertSame(
            ["use App\\Beta;", PHP_EOL, "use Zed\\Alpha;", PHP_EOL, PHP_EOL],
            $result
        );
    }

    public function testCompileToStringPrependsNamespacesFromClosureTypes(): void
    {
        $closure = function (ObjectWithSetState $object): ObjectWithSetState {
            return $object;
        };

        $reflection = new ReflectionClass(VarExport::class);
        $method     = $reflection->getMethod('compileToString');
        $value      = $method->invoke(new VarExport(), [$closure]);
        $this->assertIsString($value);
        $output     = $this->normalize($value);

        $this->assertStringContainsString('use Tests\\DocBlockGenerator\\Fixtures\\ObjectWithSetState;', $output);
        $this->assertStringContainsString('declare(strict_types=1);', $output);
    }

    public function testWrapClosureCapturesMultipleVariables(): void
    {
        $value = 'captured';
        $count = 3;
        $closure = function () use ($value, $count) {
            return $value . $count;
        };

        $output = $this->normalize((new VarExport())->export(['closure' => $closure]));

        $this->assertStringContainsString('(function() {', $output);
        $this->assertStringContainsString("\$value = 'captured';", $output);
        $this->assertStringContainsString('$count = 3;', $output);
        $this->assertStringContainsString('return function () use (', $output);
        $this->assertStringContainsString('})()', $output);
    }

    public function testWrapClosureCapturesSingleObjectVariable(): void
    {
        $object  = new ObjectWithSetState();
        $closure = function () use ($object) {
            return $object;
        };

        $output = $this->normalize((new VarExport())->export(['closure' => $closure]));

        $this->assertStringContainsString('(function() {', $output);
        $this->assertStringContainsString(
            '$object = Tests\\DocBlockGenerator\\Fixtures\\ObjectWithSetState::__set_state(',
            $output
        );
        $this->assertStringContainsString('})()', $output);
    }

    public function testWrapClosureWithoutCapturedVariables(): void
    {
        $exporter = new VarExport();

        $this->invokePrivate($exporter, 'wrapClosure', [['function () {', '    return 1;', '}'], []]);

        $buffer = $this->invokePrivate($exporter, 'getBuffer');
        $this->assertIsString($buffer);
        $output = $this->normalize($buffer);

        $this->assertStringContainsString('(function() {', $output);
        $this->assertStringContainsString('return function () {', $output);
        $this->assertStringContainsString('})()', $output);
    }

    /**
     * @return iterable<string, array{array<array-key, mixed>, bool, bool, int}>
     */
    public static function provideArrayElementCases(): iterable
    {
        $arrays = [
            'empty' => [],
            'one' => ['a' => 1],
            'two equal' => ['a' => 1, 'b' => 2],
            'three equal' => ['a' => 1, 'b' => 2, 'c' => 3],
            'two varying' => ['a' => 1, 'bb' => 2],
            'three varying' => ['a' => 1, 'bb' => 2, 'ccc' => 3],
            'one integer key' => [0 => 1],
            'two integer keys' => [0 => 1, 1 => 2],
            'mixed keys' => ['a' => 1, 2 => 'x', 'ccc' => 3],
            'empty string key' => ['' => 1, 'b' => 2],
        ];

        foreach ($arrays as $name => $elements) {
            yield "array {$name}" => [$elements, true, false, 0];
            yield "array {$name} aligned" => [$elements, true, true, 0];
            yield "array {$name} aligned indented" => [$elements, true, true, 1];
            yield "object {$name}" => [$elements, false, false, 0];
            yield "object {$name} indented" => [$elements, false, false, 1];
        }
    }

    /**
     * @param array<array-key, mixed> $elements
     */
    #[DataProvider('provideArrayElementCases')]
    public function testWriteArrayElementsPathVariants(
        array $elements,
        bool $isArrayMode,
        bool $align,
        int $indentLevel
    ): void {
        $exporter = new VarExport();
        $exporter->setAlignArray($align)->setIndentationLevel($indentLevel);

        $this->invokePrivate($exporter, 'writeArrayElements', [$elements, $isArrayMode]);

        $buffer = $this->invokePrivate($exporter, 'getBuffer');
        $this->assertIsString($buffer);
        $this->assertSame($buffer, $this->normalize($buffer));
    }

    /**
     * @return iterable<string, array{array<int, string>}>
     */
    public static function provideClosureLineCases(): iterable
    {
        yield 'no lines' => [[]];
        yield 'single line' => [['function () {']];
        yield 'two lines' => [['function () {', '    return 1;']];
        yield 'two lines with blank' => [['function () {', '']];
        yield 'three lines with blank' => [['function () {', '', '    return 1;']];
        yield 'three lines' => [['function () {', '    return 1;', '}']];
        yield 'blank first' => [['', '    return 1;']];
        yield 'whitespace line' => [['function () {', '   ']];
    }

    /**
     * @param array<int, string> $lines
     */
    #[DataProvider('provideClosureLineCases')]
    public function testWriteClosureLinesPathVariants(array $lines): void
    {
        $exporter = new VarExport();

        $this->invokePrivate($exporter, 'writeClosureLines', [$lines]);

        $buffer = $this->invokePrivate($exporter, 'getBuffer');
        $this->assertIsString($buffer);
        $output = $this->normalize($buffer);

        if ([] !== $lines) {
            $this->assertStringContainsString(trim($lines[0]), $output);
        } else {
            $this->assertSame('', $output);
        }
    }
}
