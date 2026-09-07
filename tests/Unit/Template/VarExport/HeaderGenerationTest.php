<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use Omega\Template\VarExport;
use ReflectionClass;

covers(VarExport::class);

it('generates header', function (): void {
    $exporter   = new VarExport();
    $reflection = new ReflectionClass($exporter);
    $method     = $reflection->getMethod('compileToString');
    $method->setAccessible(true);
    $output = $method->invoke($exporter, []);

    expect($output)->toStartWith('<?php');
    expect($output)->toContain('declare(strict_types=1);');
    expect($output)->toContain('// auto-generated file, do not edit!');
    expect($output)->toContain('return ');
});