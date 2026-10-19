<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function array_diff;
use function escapeshellarg;
use function exec;
use function file_get_contents;
use function implode;
use function is_dir;
use function mkdir;
use function preg_replace;
use function rmdir;
use function scandir;
use function str_replace;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

#[CoversClass(VarExport::class)]
final class FileOperationsTest extends TestCase
{
    private string $tempDir;

    private function varexportDeleteDirectory(string $dir): void
    {
        if (false === is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->varexportDeleteDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = __DIR__ . DIRECTORY_SEPARATOR . uniqid('varexport_test_');
        if (false === is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->varexportDeleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    public function testCompilesToFileSuccessfully(): void
    {
        $varExport = new VarExport();
        $data      = ['key' => 'value', 'number' => 123];
        $filePath  = $this->tempDir . DIRECTORY_SEPARATOR . 'test_output.php';

        $expectedContent = <<<'PHP'
        <?php

        declare(strict_types=1);

        // auto-generated file, do not edit!
        // generated on %date%

        return [
            'key' => 'value',
            'number' => 123,
        ];

        PHP;
        $expectedContent = str_replace(["\r\n", "\r"], "\n", $expectedContent);

        $result = $varExport->compile($data, $filePath);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);

        $fileContent = file_get_contents($filePath);
        $this->assertIsString($fileContent);

        $fileContent = preg_replace('/^(\/\/ generated on ).*$/m', '$1%date%', (string) $fileContent);

        $fileContent = str_replace(["\r\n", "\r"], "\n", (string) $fileContent);

        $this->assertEquals($expectedContent, $fileContent);
    }

    public function testCompilesToFileAndCreatesDirectoryIfNotExists(): void
    {
        $varExport = new VarExport();
        $data      = ['item1' => 'value1'];

        $newDirPath = $this->tempDir . DIRECTORY_SEPARATOR . 'new_dir';
        $filePath   = $newDirPath . DIRECTORY_SEPARATOR . 'file_in_new_dir.php';

        $this->assertFalse(is_dir($newDirPath));

        $expectedContent = <<<'PHP'
        <?php

        declare(strict_types=1);

        // auto-generated file, do not edit!
        // generated on %date%

        return [
            'item1' => 'value1',
        ];

        PHP;
        $expectedContent = str_replace(["\r\n", "\r"], "\n", $expectedContent);

        $result = $varExport->compile($data, $filePath);

        $this->assertTrue($result);
        $this->assertTrue(is_dir($newDirPath));
        $this->assertFileExists($filePath);

        $fileContent = file_get_contents($filePath);
        $this->assertIsString($fileContent);

        $fileContent = preg_replace('/^(\/\/ generated on ).*$/m', '$1%date%', (string) $fileContent);

        $fileContent = str_replace(["\r\n", "\r"], "\n", (string) $fileContent);

        $this->assertEquals($expectedContent, $fileContent);
    }

    public function testCompilesToStringWithoutHeaders(): void
    {
        $varExport = new VarExport();
        $data      = ['test' => 'no headers'];

        $output = $varExport->export($data);

        $expected = <<<'PHP'
        [
            'test' => 'no headers',
        ]
        PHP;

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
        $this->assertStringNotContainsString('<?php', $output);
        $this->assertStringNotContainsString('declare(strict_types=1);', $output);
        $this->assertStringNotContainsString('// auto-generated file', $output);
    }

    public function testEnsuresOutputFileIsValidPhp(): void
    {
        $varExport = new VarExport();
        $data      = ['valid' => true, 'nested' => ['foo' => 'bar']];
        $filePath  = $this->tempDir . DIRECTORY_SEPARATOR . 'valid_php_output.php';

        $result = $varExport->compile($data, $filePath);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);

        $command = 'php -l ' . escapeshellarg($filePath);
        exec($command, $output, $returnCode);

        $this->assertEquals(0, $returnCode);
        $this->assertStringContainsString('No syntax errors detected', implode("\n", $output));
    }

    public function testEnsuresOutputFileCanBeRequiredAndExecuted(): void
    {
        $varExport = new VarExport();
        $data      = ['foo' => 'bar', 'count' => 123, 'status' => true];
        $filePath  = $this->tempDir . DIRECTORY_SEPARATOR . 'executable_output.php';

        $result = $varExport->compile($data, $filePath);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);

        $requiredValue = require $filePath;

        $this->assertEquals($data, $requiredValue);
    }

    public function testEnsuresCompiledArrayMatchesOriginalArray(): void
    {
        $varExport = new VarExport();
        $data      = ['foo' => 'baz', 'list' => [1, 2, 3]];
        $filePath  = $this->tempDir . DIRECTORY_SEPARATOR . 'matches_original_array.php';

        $result = $varExport->compile($data, $filePath);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);

        $requiredValue = require $filePath;

        $this->assertEquals($data, $requiredValue);
    }
}
