<?php

declare(strict_types=1);

namespace Tests\Config;

use Omega\Config\ConfigBuilder;
use Omega\Config\MergeStrategy;
use Omega\Config\Source\ArrayConfig;
use PHPUnit\Framework\TestCase;

class ConfigBuilderTest extends TestCase
{

    private ConfigBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ConfigBuilder();
    }

    public function testShouldProduceEmptyConfigurationObjectIfNoSources(): void
    {
        $this->assertEmpty($this->builder->build()->getAll());
    }

    public function should_accept_configuration_source(): void
    {
        $content = ['key' => 'value'];

        $this->assertEquals(
            $content,
            $this->builder
                ->addConfiguration(new ArrayConfig($content))
                ->build()
                ->getAll()
        );
    }

    public function testShouldMergeConfigurationSourceContents(): void
    {
        $source_1 = new ArrayConfig(['key' => 'value']);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $this->assertEquals(
            [
                'key'         => 'value',
                'another_key' => 'another_value',
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2)
                ->build()
                ->getAll()
        );
    }

    public function testShouldMergeConfigurationSourceContentsRecursively(): void
    {
        $source_1 = new ArrayConfig(['nested' => ['key' => 'value']]);
        $source_2 = new ArrayConfig(['nested' => ['another_key' => 'another_value']]);

        $this->assertEquals(
            [
                'nested' => [
                    'key'         => 'value',
                    'another_key' => 'another_value',
                ],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2)
                ->build()
                ->getAll()
        );
    }

    public function testShouldReplaceIndexedArraysInConfigurationSSourceContents(): void
    {
        $source_1 = new ArrayConfig(['key' => [1, 2, 3]]);
        $source_2 = new ArrayConfig(['key' => [1, 2]]);

        $this->assertEquals(
            [
                'key' => [1, 2],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2)
                ->build()
                ->getAll()
        );
    }

    public function testShouldMergeIndexedArraysInConfigurationSourceContents(): void
    {
        $source_1 = new ArrayConfig(['key' => [1, 2, 3]]);
        $source_2 = new ArrayConfig(['key' => [3, 4, 5]]);

        $this->assertEquals(
            [
                'key' => [1, 2, 3, 4, 5],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2)
                ->build(MergeStrategy::from(MergeStrategy::MERGE_INDEXED))
                ->getAll()
        );
    }

    public function testShouldMergeConfigurationSourceContentsAtKey(): void
    {
        $source_1 = new ArrayConfig(['key' => 'value']);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $this->assertEquals(
            [
                'key'    => 'value',
                'nested' => [
                    'another_key' => 'another_value',
                ],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2, 'nested')
                ->build()
                ->getAll()
        );
    }

    public function testShouldMergeConfigurationSourceContentsAtNestedKey(): void
    {
        $source_1 = new ArrayConfig(['key' => 'value']);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $this->assertEquals(
            [
                'key'    => 'value',
                'nested' => [
                    'section' => [
                        'another_key' => 'another_value',
                    ],
                ],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2, 'nested.section')
                ->build()
                ->getAll()
        );
    }

    public function testShouldMergeConfigurationSourceContentsAtExistingKey(): void
    {
        $source_1 = new ArrayConfig(['nested' => ['key' => 'value']]);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $this->assertEquals(
            [
                'nested' => [
                    'key'         => 'value',
                    'another_key' => 'another_value',
                ],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2, 'nested')
                ->build()
                ->getAll()
        );
    }
}
