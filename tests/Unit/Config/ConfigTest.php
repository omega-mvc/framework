<?php

declare(strict_types=1);

namespace Tests\Config;

use Omega\Config\ConfigRepository;
use Omega\Config\ConfigRepositoryInterface;
use Omega\Config\MergeStrategy;

use function class_implements;

covers(ConfigRepository::class);
covers(MergeStrategy::class);

beforeEach(function (): void {
    $this->configuration = new ConfigRepository();
});

it('should implement config repository interface', function (): void {
    expect(class_implements($this->configuration::class))->toContain(ConfigRepositoryInterface::class);
});

it('should be empty by default', function (): void {
    expect($this->configuration->getAll())->toBeEmpty();
});

it('should accept store', function (): void {
    $store = ['key' => 'value'];
    $configuration = new ConfigRepository($store);

    expect($configuration->getAll())->toEqual($store);
});

it('should return all values', function (): void {
    $this->configuration->set('key', 'value');

    expect($this->configuration->getAll())->toEqual(['key' => 'value']);
});

it('should determine if key has value', function (): void {
    expect($this->configuration->has('key'))->toBeFalse();

    $this->configuration->set('key', 'value');

    expect($this->configuration->has('key'))->toBeTrue();
});

it('should determine if key has nested value', function (): void {
    expect($this->configuration->has('nested.key'))->toBeFalse();

    $this->configuration->set('nested', ['key' => 'value']);

    expect($this->configuration->has('nested.key'))->toBeTrue();
});

it('should return null if key not found', function (): void {
    expect($this->configuration->get('key'))->toBeNull();
});

it('should return null if nested key not found', function (): void {
    expect($this->configuration->get('nested.key'))->toBeNull();
});

it('should return default value if provided', function (): void {
    expect($this->configuration->get('key', 'test'))->toBe('test');
});

it('should return value for key', function (): void {
    $configuration = new ConfigRepository(['key' => 'value']);

    expect($configuration->get('key'))->toBe('value');
});

it('should return value for nested key', function (): void {
    $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);

    expect($configuration->get('nested.key'))->toBe('value');
});

it('should set value for key', function (): void {
    $this->configuration->set('key', 'value');

    expect($this->configuration->get('key'))->toBe('value');
});

it('should set value for nested key', function (): void {
    $this->configuration->set('nested.key', 'value');

    expect($this->configuration->get('nested'))->toEqual(['key' => 'value']);
});

it('should remove value for key', function (): void {
    $configuration = new ConfigRepository(['key' => 'value']);
    $configuration->remove('key');

    expect($configuration->has('key'))->toBeFalse();

    $this->configuration->remove('non_existing_key');
});

it('should remove value for nested key', function (): void {
    $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);
    $configuration->remove('nested.key');

    expect($configuration->has('nested.key'))->toBeFalse();
    expect($configuration->get('nested'))->toBeArray();
    expect($configuration->get('nested'))->toBeEmpty();

    $this->configuration->remove('nested.non_existing_key');
});

it('should clear all values', function (): void {
    $configuration = new ConfigRepository(['key' => 'value']);
    $configuration->clear();

    expect($configuration->getAll())->toBeEmpty();
});

it('should merge configuration', function (): void {
    $configuration = new ConfigRepository(['key' => 'value']);
    $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

    $configuration->merge($anotherConfiguration);

    expect($configuration->getAll())->toEqual([
        'key'         => 'value',
        'another_key' => 'another_value',
    ]);
});

it('should merge configuration at key', function (): void {
    $configuration = new ConfigRepository(['key' => 'value']);
    $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

    $configuration->merge($anotherConfiguration, 'nested');

    expect($configuration->getAll())->toEqual([
        'key'    => 'value',
        'nested' => [
            'another_key' => 'another_value',
        ],
    ]);
});

it('should merge configuration at nested key', function (): void {
    $configuration = new ConfigRepository(['key' => 'value']);
    $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

    $configuration->merge($anotherConfiguration, 'nested.section');

    expect($configuration->getAll())->toEqual([
        'key'    => 'value',
        'nested' => [
            'section' => [
                'another_key' => 'another_value',
            ],
        ],
    ]);
});

it('should merge configuration at existing key', function (): void {
    $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);
    $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

    $configuration->merge($anotherConfiguration, 'nested.section');

    expect($configuration->getAll())->toEqual([
        'nested' => [
            'key'     => 'value',
            'section' => [
                'another_key' => 'another_value',
            ],
        ],
    ]);
});

it('should merge associative arrays', function (): void {
    $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);
    $anotherConfiguration = new ConfigRepository(['nested' => ['another_key' => 'another_value']]);

    $configuration->merge($anotherConfiguration);

    expect($configuration->getAll())->toEqual([
        'nested' => [
            'key'         => 'value',
            'another_key' => 'another_value',
        ],
    ]);
});

it('should replace indexed arrays', function (): void {
    $configuration = new ConfigRepository(['indexed' => [1, 2, 3]]);
    $anotherConfiguration = new ConfigRepository(['indexed' => [1, 2]]);

    $configuration->merge($anotherConfiguration);

    expect($configuration->getAll())->toEqual(['indexed' => [1, 2]]);
});

it('should merge indexed arrays', function (): void {
    $configuration = new ConfigRepository(['indexed' => [1, 2, 3]]);
    $anotherConfiguration = new ConfigRepository(['indexed' => [3, 4, 5]]);

    $configuration->merge($anotherConfiguration, null, MergeStrategy::MERGE_INDEXED);

    expect($configuration->getAll())->toEqual(['indexed' => [1, 2, 3, 4, 5]]);
});