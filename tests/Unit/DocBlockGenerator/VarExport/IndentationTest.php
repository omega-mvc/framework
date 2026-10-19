<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\VarExport;

use Omega\DocBlockGenerator\VarExport;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function str_replace;

#[CoversClass(VarExport::class)]
final class IndentationTest extends TestCase
{
    public function testUses2SpaceIndentationConsistently(): void
    {
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

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testUses4SpaceIndentationConsistently(): void
    {
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

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testUses8SpaceIndentationConsistently(): void
    {
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

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testUses1TabIndentationConsistently(): void
    {
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

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testUses2TabIndentationConsistently(): void
    {
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

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testMaintainsNestedArrayIndentationLevels(): void
    {
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

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testNormalizesClosureIndentation(): void
    {
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

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }

    public function testHandlesMixedIndentationInSource(): void
    {
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

        $this->assertEquals(str_replace(["\r\n", "\r"], "\n", $expected), str_replace(["\r\n", "\r"], "\n", $output));
    }
}
