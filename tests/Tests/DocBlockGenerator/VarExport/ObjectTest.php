<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\DocBlockGenerator\Fixtures\ObjectWithSetState;
use Tests\DocBlockGenerator\Fixtures\ObjectWithVisibility;
use Tests\DocBlockGenerator\Fixtures\ObjectWithoutSetState;
use Tests\TestCase;

use function file_put_contents;
use function str_replace;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

#[CoversClass(VarExport::class)]
final class ObjectTest extends TestCase
{
    public function testCompilesObjectWithSetState(): void
    {
        $obj      = new ObjectWithSetState();
        $exporter = new VarExport();
        $exported = $exporter->export([$obj]);

        $this->assertStringContainsString('__set_state', $exported);

        $file         = tempnam(sys_get_temp_dir(), 'test');
        $file_content = <<<PHP
        <?php

        use Tests\DocBlockGenerator\Fixtures\ObjectWithSetState;

        return {$exported};
        PHP;
        file_put_contents($file, $file_content);
        $imported = require $file;
        unlink($file);

        $this->assertEquals([$obj], $imported);
    }

    public function testCompilesStdClassObjectByDefault(): void
    {
        $obj       = new stdClass();
        $obj->name = 'test';
        $obj->age  = 99;

        $exporter = new VarExport();
        $exported = $exporter->export(['obj' => $obj]);

        $expected = <<<'PHP'
        [
            'obj' => (object) [
                'name' => 'test',
                'age'  => 99,
            ],
        ]
        PHP;

        $this->assertEquals(
            str_replace(["\r\n", "\r"], "\n", trim($expected)),
            str_replace(["\r\n", "\r"], "\n", trim($exported))
        );

        $file = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($file, "<?php return {$exported};");
        $imported = require $file;
        unlink($file);

        $this->assertEquals(['obj' => $obj], $imported);
    }

    public function testCompilesObjectWithoutSetState(): void
    {
        $obj    = new ObjectWithoutSetState();
        $obj->a = 10;

        $exporter = new VarExport();
        $exported = $exporter->export([$obj]);

        $this->assertStringContainsString('ObjectWithoutSetState::__set_state', $exported);
        $this->assertStringContainsString('ObjectWithoutSetState', $exported);
    }

    public function testCompilesObjectWithPrivateAndProtectedProperties(): void
    {
        $obj = new ObjectWithVisibility();

        $exporter = new VarExport();
        $exported = $exporter->export([$obj]);

        $this->assertStringContainsString("'public' => 1", $exported);
        $this->assertStringContainsString("'protected' => 2", $exported);
        $this->assertStringContainsString("'private' => 3", $exported);

        $file         = tempnam(sys_get_temp_dir(), 'test');
        $file_content = <<<PHP
        <?php

        use Tests\DocBlockGenerator\Fixtures\ObjectWithVisibility;

        return {$exported};
        PHP;
        file_put_contents($file, $file_content);
        $imported = require $file;
        unlink($file);

        if (!is_array($imported) || !isset($imported[0]) || !$imported[0] instanceof ObjectWithVisibility) {
            throw new \RuntimeException('Imported file must contain an ObjectWithVisibility instance.');
        }

        $this->assertEquals(1, $imported[0]->getPublic());
        $this->assertEquals(2, $imported[0]->getProtected());
        $this->assertEquals(3, $imported[0]->getPrivate());
    }
}
