<?php

declare(strict_types=1);

namespace Tests\Config;

use Omega\Config\ConfigBuilder;
use Omega\Config\MergeStrategy;
use Omega\Config\Source\ArrayConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ConfigBuilder::class)]
#[CoversClass(MergeStrategy::class)]
#[CoversClass(ArrayConfig::class)]
final class ConfigBuilderTest extends TestCase
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

    public function testShouldAcceptConfigurationSource(): void
    {
        $content = ['key' => 'value'];

        $configuration = $this->builder
            ->addConfiguration(new ArrayConfig($content))
            ->build()
            ->getAll();

        $this->assertEquals($content, $configuration);
    }

    public function testShouldMergeConfigurationSourceContents(): void
    {
        $source_1 = new ArrayConfig(['key' => 'value']);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $configuration = $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2)
            ->build()
            ->getAll();

        $this->assertEquals([
            'key'         => 'value',
            'another_key' => 'another_value',
        ], $configuration);
    }

    public function testShouldMergeConfigurationSourceContentsRecursively(): void
    {
        $source_1 = new ArrayConfig(['nested' => ['key' => 'value']]);
        $source_2 = new ArrayConfig(['nested' => ['another_key' => 'another_value']]);

        $configuration = $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2)
            ->build()
            ->getAll();

        $this->assertEquals([
            'nested' => [
                'key'         => 'value',
                'another_key' => 'another_value',
            ],
        ], $configuration);
    }

    public function testShouldReplaceIndexedArraysInConfigurationSourceContents(): void
    {
        $source_1 = new ArrayConfig(['key' => [1, 2, 3]]);
        $source_2 = new ArrayConfig(['key' => [1, 2]]);

        $configuration = $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2)
            ->build()
            ->getAll();

        $this->assertEquals([
            'key' => [1, 2],
        ], $configuration);
    }

    public function testShouldMergeIndexedArraysInConfigurationSourceContents(): void
    {
        $source_1 = new ArrayConfig(['key' => [1, 2, 3]]);
        $source_2 = new ArrayConfig(['key' => [3, 4, 5]]);

        $configuration = $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2)
            ->build(MergeStrategy::MERGE_INDEXED)
            ->getAll();

        $this->assertEquals([
            'key' => [1, 2, 3, 4, 5],
        ], $configuration);
    }

    public function testShouldMergeConfigurationSourceContentsAtKey(): void
    {
        $source_1 = new ArrayConfig(['key' => 'value']);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $configuration = $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2, 'nested')
            ->build()
            ->getAll();

        $this->assertEquals([
            'key'    => 'value',
            'nested' => [
                'another_key' => 'another_value',
            ],
        ], $configuration);
    }

    public function testShouldMergeConfigurationSourceContentsAtNestedKey(): void
    {
        $source_1 = new ArrayConfig(['key' => 'value']);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $configuration = $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2, 'nested.section')
            ->build()
            ->getAll();

        $this->assertEquals([
            'key'    => 'value',
            'nested' => [
                'section' => [
                    'another_key' => 'another_value',
                ],
            ],
        ], $configuration);
    }

    public function testShouldMergeConfigurationSourceContentsAtExistingKey(): void
    {
        $source_1 = new ArrayConfig(['nested' => ['key' => 'value']]);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $configuration = $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2, 'nested')
            ->build()
            ->getAll();

        $this->assertEquals([
            'nested' => [
                'key'         => 'value',
                'another_key' => 'another_value',
            ],
        ], $configuration);
    }
}
