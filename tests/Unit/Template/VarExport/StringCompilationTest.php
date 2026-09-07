<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use Omega\Template\VarExport;

use function str_replace;

covers(VarExport::class);

it('compiles string with unicode characters correctly', function (): void {
    $varExport = new VarExport();
    $exported  = $varExport->export(['Hello, 世界']);

    $expected = <<<'PHP'
[
    0 => 'Hello, 世界',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $exported));
});

it('compiles an empty string correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export(['']);

    $expected = <<<'PHP'
[
    0 => '',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles string with single quotes correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export(["it's a string"]);

    $expected = <<<'PHP'
[
    0 => 'it\'s a string',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles string with double quotes correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export(['This is a "quoted" string']);

    $expected = <<<'PHP'
[
    0 => 'This is a "quoted" string',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles string with backslashes correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export(['a\\b']);

    $expected = <<<'PHP'
[
    0 => 'a\\b',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles string with special characters correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export(['!@#$%^&*()-=_+[]{}|;:,.<>/?']);

    $expected = <<<'PHP'
[
    0 => '!@#$%^&*()-=_+[]{}|;:,.<>/?',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles string with newlines correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export(["First line\nSecond line"]);

    $expected = <<<'PHP'
[
    0 => 'First line
Second line',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});
