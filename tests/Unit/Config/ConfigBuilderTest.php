<?php

declare(strict_types=1);

namespace Tests\Config;

use Omega\Config\ConfigBuilder;
use Omega\Config\MergeStrategy;
use Omega\Config\Source\ArrayConfig;

covers(ConfigBuilder::class);
covers(MergeStrategy::class);
covers(ArrayConfig::class);

beforeEach(function (): void {
    $this->builder = new ConfigBuilder();
});

it('should produce empty configuration object if no sources', function (): void {
    expect($this->builder->build()->getAll())->toBeEmpty();
});

it('should accept configuration source', function (): void {
    $content = ['key' => 'value'];

    expect(
        $this->builder
            ->addConfiguration(new ArrayConfig($content))
            ->build()
            ->getAll()
    )->toEqual($content);
});

it('should merge configuration source contents', function (): void {
    $source_1 = new ArrayConfig(['key' => 'value']);
    $source_2 = new ArrayConfig(['another_key' => 'another_value']);

    expect(
        $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2)
            ->build()
            ->getAll()
    )->toEqual([
        'key'         => 'value',
        'another_key' => 'another_value',
    ]);
});

it('should merge configuration source contents recursively', function (): void {
    $source_1 = new ArrayConfig(['nested' => ['key' => 'value']]);
    $source_2 = new ArrayConfig(['nested' => ['another_key' => 'another_value']]);

    expect(
        $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2)
            ->build()
            ->getAll()
    )->toEqual([
        'nested' => [
            'key'         => 'value',
            'another_key' => 'another_value',
        ],
    ]);
});

it('should replace indexed arrays in configuration source contents', function (): void {
    $source_1 = new ArrayConfig(['key' => [1, 2, 3]]);
    $source_2 = new ArrayConfig(['key' => [1, 2]]);

    expect(
        $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2)
            ->build()
            ->getAll()
    )->toEqual([
        'key' => [1, 2],
    ]);
});

it('should merge indexed arrays in configuration source contents', function (): void {
    $source_1 = new ArrayConfig(['key' => [1, 2, 3]]);
    $source_2 = new ArrayConfig(['key' => [3, 4, 5]]);

    expect(
        $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2)
            ->build(MergeStrategy::MERGE_INDEXED)
            ->getAll()
    )->toEqual([
        'key' => [1, 2, 3, 4, 5],
    ]);
});

it('should merge configuration source contents at key', function (): void {
    $source_1 = new ArrayConfig(['key' => 'value']);
    $source_2 = new ArrayConfig(['another_key' => 'another_value']);

    expect(
        $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2, 'nested')
            ->build()
            ->getAll()
    )->toEqual([
        'key'    => 'value',
        'nested' => [
            'another_key' => 'another_value',
        ],
    ]);
});

it('should merge configuration source contents at nested key', function (): void {
    $source_1 = new ArrayConfig(['key' => 'value']);
    $source_2 = new ArrayConfig(['another_key' => 'another_value']);

    expect(
        $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2, 'nested.section')
            ->build()
            ->getAll()
    )->toEqual([
        'key'    => 'value',
        'nested' => [
            'section' => [
                'another_key' => 'another_value',
            ],
        ],
    ]);
});

it('should merge configuration source contents at existing key', function (): void {
    $source_1 = new ArrayConfig(['nested' => ['key' => 'value']]);
    $source_2 = new ArrayConfig(['another_key' => 'another_value']);

    expect(
        $this->builder
            ->addConfiguration($source_1)
            ->addConfiguration($source_2, 'nested')
            ->build()
            ->getAll()
    )->toEqual([
        'nested' => [
            'key'         => 'value',
            'another_key' => 'another_value',
        ],
    ]);
});