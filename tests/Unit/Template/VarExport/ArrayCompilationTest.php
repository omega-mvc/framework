<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use Omega\Template\VarExport;
use Omega\Template\VarExport\Value\Constant;

covers(Constant::class);
covers(VarExport::class);

it('compiles an empty array correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([]);

    $expected = <<<'PHP'
[]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles an indexed array correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([1, 2, 3]);

    $expected = <<<'PHP'
[
    0 => 1,
    1 => 2,
    2 => 3,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles an associative array correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([
        'name'    => 'Savanna',
        'version' => '1.0',
    ]);

    $expected = <<<'PHP'
[
    'name' => 'Savanna',
    'version' => '1.0',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a mixed array correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([
        0         => 'first',
        'name'    => 'Savanna',
        1         => 'second',
        'version' => '1.0',
    ]);

    $expected = <<<'PHP'
[
    0 => 'first',
    'name' => 'Savanna',
    1 => 'second',
    'version' => '1.0',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles array with non-sequential numeric keys', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([
        0 => 'first',
        2 => 'third',
        1 => 'second',
    ]);

    $expected = <<<'PHP'
[
    0 => 'first',
    2 => 'third',
    1 => 'second',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles nested arrays (depth 2) correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([
        'level1_key1' => 'value1',
        'level1_key2' => [
            'level2_key1' => 'value2',
            'level2_key2' => 123,
        ],
        'level1_key3' => true,
    ]);

    $expected = <<<'PHP'
[
    'level1_key1' => 'value1',
    'level1_key2' => [
        'level2_key1' => 'value2',
        'level2_key2' => 123,
    ],
    'level1_key3' => true,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles nested arrays (depth 5) correctly', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([
        'l1k1' => [
            'l2k1' => [
                'l3k1' => [
                    'l4k1' => [
                        'l5k1' => 'deep_value',
                    ],
                ],
            ],
        ],
        'l1k2' => 'value',
    ]);

    $expected = <<<'PHP'
[
    'l1k1' => [
        'l2k1' => [
            'l3k1' => [
                'l4k1' => [
                    'l5k1' => 'deep_value',
                ],
            ],
        ],
    ],
    'l1k2' => 'value',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles array with various value types mixed', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export([
        'string'       => 'hello',
        'integer'      => 123,
        'float'        => 1.23,
        'boolean'      => true,
        'null'         => null,
        'nested_array' => [
            'key' => 'value',
        ],
    ]);

    $expected = <<<'PHP'
[
    'string' => 'hello',
    'integer' => 123,
    'float' => 1.23,
    'boolean' => true,
    'null' => null,
    'nested_array' => [
        'key' => 'value',
    ],
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles a large array correctly', function (): void {
    $varExport = new VarExport();

    $largeArray = [];
    for ($i = 0; $i < 1000; $i++) {
        $largeArray['key_' . $i] = 'value_' . $i;
    }

    $output = $varExport->export($largeArray);

    $expectedParts = [];
    foreach ($largeArray as $key => $value) {
        $expectedParts[] = sprintf("    '%s' => '%s',", $key, $value);
    }
    $expected = "[\n" . implode("\n", $expectedParts) . "\n]";

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles an array containing a reference', function (): void {
    $varExport = new VarExport();

    $refValue = 'original';
    $array    = [
        'key1' => 'value1',
        'key2' => &$refValue,
        'key3' => 'value3',
    ];

    $output = $varExport->export($array);

    $expected = <<<'PHP'
[
    'key1' => 'value1',
    'key2' => 'original',
    'key3' => 'value3',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('compiles an array containing a constant', function (): void {
    $varExport = new VarExport();
    $output    = $varExport->export(['my_constant' => new Constant('MY_TEST_CONSTANT')]);

    $expected = <<<'PHP'
[
    'my_constant' => MY_TEST_CONSTANT,
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});