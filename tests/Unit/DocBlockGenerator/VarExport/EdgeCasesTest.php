<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use InvalidArgumentException;
use Omega\DocBlockGenerator\VarExport;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\TestCase;

use function fopen;
use function str_repeat;

#[CoversClass(VarExport::class)]
final class EdgeCasesTest extends TestCase
{
    public function testThrowsExceptionOnResourceType(): void
    {
        $resource = fopen('php://memory', 'r');
        $exporter = new VarExport();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot compile resource type');
        $exporter->export([$resource]);
    }

    public function testCanHandleDeeplyNestedArray(): void
    {
        $array   = [];
        $current = &$array;
        for ($i = 0; $i < 50; $i++) {
            $current['next'] = [];
            $current         = &$current['next'];
        }
        $current['end'] = true;

        $exporter = new VarExport();
        $exported = $exporter->export($array);

        $this->assertStringContainsString('\'end\' => true', $exported);
        $this->assertStringContainsString(str_repeat('    ', 50), $exported);
    }

    public function testCanHandleBinaryDataInStrings(): void
    {
        $binary   = "\x00\x01\x02\x03\xff";
        $exporter = new VarExport();
        $exported = $exporter->export([$binary]);

        $this->assertNotEmpty($exported);

        $file     = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($file, "<?php return {$exported};");
        $imported = require $file;
        unlink($file);

        $this->assertEquals([$binary], $imported);
    }

    public function testCanHandleCircularReference(): void
    {
        $a       = new stdClass();
        $a->self = $a;

        $exporter = new VarExport();
        $exported = $exporter->export([$a]);

        $this->assertStringContainsString('null /* RECURSION */', $exported);
    }
}
