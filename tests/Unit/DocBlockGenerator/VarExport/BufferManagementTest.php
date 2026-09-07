<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;

covers(VarExport::class);

it('starts empty buffer', function (): void {
    $varExport = new VarExport();
    $result    = $varExport->export([]);

    expect($result)->toEqual('[]');
});

it('resets buffer after compile', function (): void {
    $varExport = new VarExport();

    $varExport->export(['foo' => 'bar']);

    $result = $varExport->export([]);

    expect($result)->toEqual('[]');
});
