<?php

declare(strict_types=1);

namespace Tests\Template\VarExport;

use Omega\Template\VarExport;

use function str_replace;

covers(VarExport::class);

it('uses 2-space indentation consistently', function (): void {
    $varExport = new VarExport();
    $varExport->setIndentation('  ');

    $array = [
        'key1' => 'value1',
        'key2' => [
            'nested_key1' => 'nested_value1',
            'nested_key2' => [
                'double_nested_key' => 'double_nested_value',
            ],
        ],
        'key3' => 'value3',
    ];

    $output = $varExport->export($array);

    $expected = <<<'PHP'
[
  'key1' => 'value1',
  'key2' => [
    'nested_key1' => 'nested_value1',
    'nested_key2' => [
      'double_nested_key' => 'double_nested_value',
    ],
  ],
  'key3' => 'value3',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('uses 4-space indentation consistently', function (): void {
    $varExport = new VarExport();
    $varExport->setIndentation('    ');

    $array = [
        'key1' => 'value1',
        'key2' => [
            'nested_key1' => 'nested_value1',
            'nested_key2' => [
                'double_nested_key' => 'double_nested_value',
            ],
        ],
        'key3' => 'value3',
    ];

    $output = $varExport->export($array);

    $expected = <<<'PHP'
[
    'key1' => 'value1',
    'key2' => [
        'nested_key1' => 'nested_value1',
        'nested_key2' => [
            'double_nested_key' => 'double_nested_value',
        ],
    ],
    'key3' => 'value3',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('uses 8-space indentation consistently', function (): void {
    $varExport = new VarExport();
    $varExport->setIndentation('        ');

    $array = [
        'key1' => 'value1',
        'key2' => [
            'nested_key1' => 'nested_value1',
            'nested_key2' => [
                'double_nested_key' => 'double_nested_value',
            ],
        ],
        'key3' => 'value3',
    ];

    $output = $varExport->export($array);

    $expected = <<<'PHP'
[
        'key1' => 'value1',
        'key2' => [
                'nested_key1' => 'nested_value1',
                'nested_key2' => [
                        'double_nested_key' => 'double_nested_value',
                ],
        ],
        'key3' => 'value3',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('uses 1-tab indentation consistently', function (): void {
    $varExport = new VarExport();
    $varExport->setIndentation("\t");

    $array = [
        'key1' => 'value1',
        'key2' => [
            'nested_key1' => 'nested_value1',
            'nested_key2' => [
                'double_nested_key' => 'double_nested_value',
            ],
        ],
        'key3' => 'value3',
    ];

    $output = $varExport->export($array);

    $expected = <<<'PHP'
[
	'key1' => 'value1',
	'key2' => [
		'nested_key1' => 'nested_value1',
		'nested_key2' => [
			'double_nested_key' => 'double_nested_value',
		],
	],
	'key3' => 'value3',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('uses 2-tab indentation consistently', function (): void {
    $varExport = new VarExport();
    $varExport->setIndentation("\t\t");

    $array = [
        'key1' => 'value1',
        'key2' => [
            'nested_key1' => 'nested_value1',
            'nested_key2' => [
                'double_nested_key' => 'double_nested_value',
            ],
        ],
        'key3' => 'value3',
    ];

    $output = $varExport->export($array);

    $expected = <<<'PHP'
[
		'key1' => 'value1',
		'key2' => [
				'nested_key1' => 'nested_value1',
				'nested_key2' => [
						'double_nested_key' => 'double_nested_value',
				],
		],
		'key3' => 'value3',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('maintains nested array indentation levels', function (): void {
    $varExport = new VarExport();

    $array = [
        'level1_key1' => 'value1',
        'level1_key2' => [
            'level2_key1' => 'value2',
            'level2_key2' => [
                'level3_key1' => 'value3',
                'level3_key2' => [
                    'level4_key1' => 'value4',
                ],
            ],
        ],
        'level1_key3' => 'value5',
    ];

    $output = $varExport->export($array);

    $expected = <<<'PHP'
[
    'level1_key1' => 'value1',
    'level1_key2' => [
        'level2_key1' => 'value2',
        'level2_key2' => [
            'level3_key1' => 'value3',
            'level3_key2' => [
                'level4_key1' => 'value4',
            ],
        ],
    ],
    'level1_key3' => 'value5',
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('normalizes closure indentation', function (): void {
    $varExport = new VarExport();
    $varExport->setIndentation('    ');

    $array = [
        'closure' => function () {
            $a = 1;

            return $a;
        },
    ];

    $output = $varExport->export($array);

    $expected = <<<'PHP'
[
    'closure' => function () {
        $a = 1;

        return $a;
    },
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});

it('handles mixed indentation in source', function (): void {
    $varExport = new VarExport();
    $varExport->setIndentation('    ');

    $array = [
        'closure' => function () {
            $a = 1;
            $b = 2;

            return $a + $b;
        },
    ];

    $output = $varExport->export($array);

    $expected = <<<'PHP'
[
    'closure' => function () {
        $a = 1;
        $b = 2;

        return $a + $b;
    },
]
PHP;

    expect(str_replace(["\r\n", "\r"], "\n", $expected))->toEqual(str_replace(["\r\n", "\r"], "\n", $output));
});
