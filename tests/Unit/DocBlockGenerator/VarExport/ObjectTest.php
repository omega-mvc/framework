<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;
use stdClass;
use Tests\DocBlockGenerator\Fixtures\ObjectWithoutSetState;
use Tests\DocBlockGenerator\Fixtures\ObjectWithSetState;
use Tests\DocBlockGenerator\Fixtures\ObjectWithVisibility;

use function file_put_contents;
use function str_replace;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

covers(VarExport::class);

it('compiles object with set state', function (): void {
    $obj      = new ObjectWithSetState();
    $exporter = new VarExport();
    $exported = $exporter->export([$obj]);

    expect($exported)->toContain('__set_state');

    $file         = tempnam(sys_get_temp_dir(), 'test');
    $file_content = <<<PHP
<?php

use Tests\DocBlockGenerator\Fixtures\ObjectWithSetState;

return {$exported};
PHP;
    file_put_contents($file, $file_content);
    $imported = require $file;
    unlink($file);

    expect($imported)->toEqual([$obj]);
});

it('compiles stdClass object by default', function (): void {
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

    expect(str_replace(["\r\n", "\r"], "\n", trim($expected)))
        ->toEqual(str_replace(["\r\n", "\r"], "\n", trim($exported)));

    $file = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($file, "<?php return {$exported};");
    $imported = require $file;
    unlink($file);

    expect($imported)->toEqual(['obj' => $obj]);
});

it('compiles object without set state', function (): void {
    $obj    = new ObjectWithoutSetState();
    $obj->a = 10;

    $exporter = new VarExport();
    $exported = $exporter->export([$obj]);

    expect($exported)->toContain('ObjectWithoutSetState::__set_state');
    expect($exported)->toContain('ObjectWithoutSetState');
});

it('compiles object with private and protected properties', function (): void {
    $obj = new ObjectWithVisibility();

    $exporter = new VarExport();
    $exported = $exporter->export([$obj]);

    expect($exported)->toContain("'public' => 1");
    expect($exported)->toContain("'protected' => 2");
    expect($exported)->toContain("'private' => 3");

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

    expect($imported[0]->getPublic())->toEqual(1);
    expect($imported[0]->getProtected())->toEqual(2);
    expect($imported[0]->getPrivate())->toEqual(3);
});
