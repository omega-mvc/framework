<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use Omega\Template\VarExport;

use function str_replace;

covers(VarExport::class);

it('compiles a positive integer correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([123]);

    $expected = <<<'PHP'
[
    0 => 123,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a negative integer correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([-456]);

    $expected = <<<'PHP'
[
    0 => -456,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a zero integer correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([0]);

    $expected = <<<'PHP'
[
    0 => 0,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a positive float correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([123.45]);

    $expected = <<<'PHP'
[
    0 => 123.45,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a negative float correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([-67.89]);

    $expected = <<<'PHP'
[
    0 => -67.89,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a float with a decimal part correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([10.0]);

    $expected = <<<'PHP'
[
    0 => 10.0,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a whole number float correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([10.0]);

    $expected = <<<'PHP'
[
    0 => 10.0,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a float in scientific notation correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([1.23e4]);

    $expected = <<<'PHP'
[
    0 => 12300.0,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a boolean true correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([true]);

    $expected = <<<'PHP'
[
    0 => true,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a boolean false correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([false]);

    $expected = <<<'PHP'
[
    0 => false,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles null correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([null]);

    $expected = <<<'PHP'
[
    0 => null,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});