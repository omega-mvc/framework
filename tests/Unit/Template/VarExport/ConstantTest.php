<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use Omega\Template\VarExport;
use Omega\Template\VarExport\Value\Constant;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const PHP_VERSION;

covers(Constant::class);
covers(VarExport::class);

it('can compile constant by name', function (): void {
    $data = [
        'php_version' => new Constant('PHP_VERSION'),
        'ds'          => new Constant('DIRECTORY_SEPARATOR'),
    ];

    $exporter = new VarExport();
    $exported = $exporter->export($data);

    expect($exported)->toContain("'php_version' => PHP_VERSION");
    expect($exported)->toContain("'ds' => DIRECTORY_SEPARATOR");

    $file = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($file, "<?php return {$exported};");
    $imported = require $file;
    unlink($file);

    expect($imported['php_version'])->toEqual(PHP_VERSION);
    expect($imported['ds'])->toEqual(DIRECTORY_SEPARATOR);
});
