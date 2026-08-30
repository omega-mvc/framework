<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Template\VarExport;
use stdClass;
use Tests\Template\Fixtures\ObjectWithoutSetState;
use Tests\Template\Fixtures\ObjectWithSetState;
use Tests\Template\Fixtures\ObjectWithVisibility;

#[CoversClass(VarExport::class)]
class ObjectTest extends TestCase
{
    /**
     * @test
     */
    public function testItCanCompileObjectWithSetState()
    {
        $obj      = new ObjectWithSetState();
        $exporter = new VarExport();
        $exported = $exporter->export([$obj]);

        $this->assertStringContainsString('__set_state', $exported);

        $file         = tempnam(sys_get_temp_dir(), 'test');
        $file_content = <<<PHP
<?php

use System\Test\Template\VarExport\ObjectWithSetState;

return {$exported};
PHP;
        file_put_contents($file, $file_content);
        $imported = require $file;
        unlink($file);

        $this->assertEquals([$obj], $imported);
    }

    /**
     * @test
     */
    public function testItCanCompileStdClassObjectByDefault()
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
        $normalizedOutput   = str_replace(["\r\n", "\r"], "\n", trim($exported));
        $normalizedExpected = str_replace(["\r\n", "\r"], "\n", trim($expected));

        $this->assertEquals($normalizedExpected, $normalizedOutput);

        $file = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($file, "<?php return {$exported};");
        $imported = require $file;
        unlink($file);

        $this->assertEquals(['obj' => $obj], $imported);
    }

    /**
     * @test
     */
    public function testItCanCompileObjectWithoutSetState()
    {
        $obj    = new ObjectWithoutSetState();
        $obj->a = 10;

        $exporter = new VarExport();
        $exported = $exporter->export([$obj]);

        $this->assertStringContainsString('ObjectWithoutSetState::__set_state', $exported);
        $this->assertStringContainsString('ObjectWithoutSetState', $exported);
    }

    /**
     * @test
     */
    public function testItCanCompileObjectWithPrivateAndProtectedProperties()
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

use System\Test\Template\VarExport\ObjectWithVisibility;

return {$exported};
PHP;
        file_put_contents($file, $file_content);
        $imported = require $file;
        unlink($file);

        $this->assertEquals(1, $imported[0]->getPublic());
        $this->assertEquals(2, $imported[0]->getProtected());
        $this->assertEquals(3, $imported[0]->getPrivate());
    }
}
