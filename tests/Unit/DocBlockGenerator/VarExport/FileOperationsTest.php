<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;

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

covers(VarExport::class);

function varexportDeleteDirectory(string $dir): void
{
    if (false === is_dir($dir)) {
        return;
    }
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? varexportDeleteDirectory("$dir/$file") : unlink("$dir/$file");
    }
    rmdir($dir);
}

beforeEach(function (): void {
    $this->tempDir = __DIR__ . DIRECTORY_SEPARATOR . uniqid('varexport_test_');
    if (false === is_dir($this->tempDir)) {
        mkdir($this->tempDir, 0777, true);
    }
});

afterEach(function (): void {
    if (is_dir($this->tempDir)) {
        varexportDeleteDirectory($this->tempDir);
    }
});

it('compiles to file successfully', function (): void {
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

    expect($result)->toBeTrue();
    expect(file_exists($filePath))->toBeTrue();

    $fileContent = file_get_contents($filePath);
    expect($fileContent)->toBeString();

    if (!is_string($fileContent)) {
        throw new \RuntimeException('Compiled file is not readable.');
    }

    $fileContent = preg_replace('/^(\/\/ generated on ).*$/m', '$1%date%', $fileContent);

    if (!is_string($fileContent)) {
        throw new \RuntimeException('Failed to normalize the compiled file.');
    }

    $fileContent = str_replace(["\r\n", "\r"], "\n", $fileContent);

    expect($expectedContent)->toEqual($fileContent);
});

it('compiles to file and creates directory if not exists', function (): void {
    $varExport = new VarExport();
    $data      = ['item1' => 'value1'];

    $newDirPath = $this->tempDir . DIRECTORY_SEPARATOR . 'new_dir';
    $filePath   = $newDirPath . DIRECTORY_SEPARATOR . 'file_in_new_dir.php';

    expect(is_dir($newDirPath))->toBeFalse();

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

    expect($result)->toBeTrue();
    expect(is_dir($newDirPath))->toBeTrue();
    expect(file_exists($filePath))->toBeTrue();

    $fileContent = file_get_contents($filePath);
    expect($fileContent)->toBeString();

    if (!is_string($fileContent)) {
        throw new \RuntimeException('Compiled file is not readable.');
    }

    $fileContent = preg_replace('/^(\/\/ generated on ).*$/m', '$1%date%', $fileContent);

    if (!is_string($fileContent)) {
        throw new \RuntimeException('Failed to normalize the compiled file.');
    }

    $fileContent = str_replace(["\r\n", "\r"], "\n", $fileContent);

    expect($expectedContent)->toEqual($fileContent);
});

it('compiles to string without headers', function (): void {
    $varExport = new VarExport();
    $data      = ['test' => 'no headers'];

    $output = $varExport->export($data);

    $expected = <<<'PHP'
[
    'test' => 'no headers',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
    expect($output)->not->toContain('<?php');
    expect($output)->not->toContain('declare(strict_types=1);');
    expect($output)->not->toContain('// auto-generated file');
});

it('ensures output file is valid php', function (): void {
    $varExport = new VarExport();
    $data      = ['valid' => true, 'nested' => ['foo' => 'bar']];
    $filePath  = $this->tempDir . DIRECTORY_SEPARATOR . 'valid_php_output.php';

    $result = $varExport->compile($data, $filePath);

    expect($result)->toBeTrue();
    expect(file_exists($filePath))->toBeTrue();

    $command = 'php -l ' . escapeshellarg($filePath);
    exec($command, $output, $returnCode);

    expect($returnCode)->toEqual(0);
    expect(implode("\n", $output))->toContain('No syntax errors detected');
});

it('ensures output file can be required and executed', function (): void {
    $varExport = new VarExport();
    $data      = ['foo' => 'bar', 'count' => 123, 'status' => true];
    $filePath  = $this->tempDir . DIRECTORY_SEPARATOR . 'executable_output.php';

    $result = $varExport->compile($data, $filePath);

    expect($result)->toBeTrue();
    expect(file_exists($filePath))->toBeTrue();

    $requiredValue = require $filePath;

    expect($requiredValue)->toEqual($data);
});

it('ensures compiled array matches original array', function (): void {
    $varExport = new VarExport();
    $data      = ['foo' => 'baz', 'list' => [1, 2, 3]];
    $filePath  = $this->tempDir . DIRECTORY_SEPARATOR . 'matches_original_array.php';

    $result = $varExport->compile($data, $filePath);

    expect($result)->toBeTrue();
    expect(file_exists($filePath))->toBeTrue();

    $requiredValue = require $filePath;

    expect($requiredValue)->toEqual($data);
});
