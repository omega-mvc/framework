<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;

use function file_put_contents;
use function fopen;
use function str_repeat;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

covers(VarExport::class);

it('throws exception on resource type', function (): void {
    $resource = fopen('php://memory', 'r');
    $exporter = new VarExport();

    expect(fn () => $exporter->export([$resource]))
        ->toThrow(\InvalidArgumentException::class, 'Cannot compile resource type');
});

it('can handle deeply nested array', function (): void {
    $array   = [];
    $current = &$array;
    for ($i = 0; $i < 50; $i++) {
        $current['next'] = [];
        $current         = &$current['next'];
    }
    $current['end'] = true;

    $exporter = new VarExport();
    $exported = $exporter->export($array);

    expect($exported)->toContain('\'end\' => true');
    expect($exported)->toContain(str_repeat('    ', 50));
});

it('can handle binary data in strings', function (): void {
    $binary   = "\x00\x01\x02\x03\xff";
    $exporter = new VarExport();
    $exported = $exporter->export([$binary]);

    expect($exported)->not->toBeEmpty();

    $file     = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($file, "<?php return {$exported};");
    $imported = require $file;
    unlink($file);

    expect($imported)->toEqual([$binary]);
});

it('can handle circular reference', function (): void {
    $a       = new \stdClass();
    $a->self = $a;

    $exporter = new VarExport();
    $exported = $exporter->export([$a]);

    expect($exported)->toContain('null /* RECURSION */');
});
