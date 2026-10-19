<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;
use Omega\DocBlockGenerator\VarExport\Value\Constant;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const PHP_VERSION;

#[CoversClass(Constant::class)]
#[CoversClass(VarExport::class)]
final class ConstantTest extends TestCase
{
    public function testCanCompileConstantByName(): void
    {
        $data = [
            'php_version' => new Constant('PHP_VERSION'),
            'ds'          => new Constant('DIRECTORY_SEPARATOR'),
        ];

        $exporter = new VarExport();
        $exported = $exporter->export($data);

        $this->assertStringContainsString("'php_version' => PHP_VERSION", $exported);
        $this->assertStringContainsString("'ds' => DIRECTORY_SEPARATOR", $exported);

        $file = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($file, "<?php return {$exported};");
        $imported = require $file;
        unlink($file);

        if (!is_array($imported)) {
            throw new \RuntimeException('Imported file must return an array.');
        }

        $this->assertEquals(PHP_VERSION, $imported['php_version']);
        $this->assertEquals(DIRECTORY_SEPARATOR, $imported['ds']);
    }
}
