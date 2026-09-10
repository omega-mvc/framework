<?php

/**
 * Part of Omega - Tests\Cache Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Cache\Storage;

use Omega\Cache\Storage\FileStorage;
use Tests\FixturesPathTrait;

covers(FileStorage::class);

uses(FixturesPathTrait::class);

beforeEach(function (): void {
    $this->storage = new FileStorage(['ttl' => 3600, 'path' => $this->setFixturePath('/fixtures/cache')]);
});

it('sets and gets a value', function (): void {
    expect($this->storage->set('key1', 'value1'))->toBeTrue();
    expect($this->storage->get('key1'))->toEqual('value1');
});

it('gets the default value when the key is not found', function (): void {
    expect($this->storage->get('non_existing_key', 'default'))->toEqual('default');
});

it('sets a value with a ttl and expires it', function (): void {
    $storage = $this->getMockBuilder(FileStorage::class)
        ->setConstructorArgs([['ttl' => 3600, 'path' => $this->setFixturePath('/fixtures/cache')]])
        ->onlyMethods(['calculateExpirationTimestamp'])
        ->getMock();

    $storage->expects($this->exactly(2))
        ->method('calculateExpirationTimestamp')
        ->willReturnOnConsecutiveCalls(
            time() + 3600,
            time() - 1
        );

    expect($storage->set('key2', 'value2', 3600))->toBeTrue();
    expect($storage->get('key2'))->toEqual('value2');

    $storage->set('key2', 'value2', 1);
    expect($storage->get('key2'))->toBeNull();
});

it('deletes a value', function (): void {
    $this->storage->set('key3', 'value3');
    expect($this->storage->delete('key3'))->toBeTrue();
    expect($this->storage->has('key3'))->toBeFalse();
});

it('returns false when deleting a non-existing key', function (): void {
    expect($this->storage->delete('non_existing_key'))->toBeFalse();
});

it('clears all values', function (): void {
    $this->storage->set('key4', 'value4');
    expect($this->storage->clear())->toBeTrue();
    expect($this->storage->has('key4'))->toBeFalse();
});

it('gets multiple values', function (): void {
    $this->storage->set('key5', 'value5');
    $this->storage->set('key6', 'value6');
    $result = $this->storage->getMultiple(['key5', 'key6', 'non_existing_key'], 'default');

    expect($result)->toEqual(['key5' => 'value5', 'key6' => 'value6', 'non_existing_key' => 'default']);
});

it('sets multiple values', function (): void {
    $values = ['key7' => 'value7', 'key8' => 'value8'];
    expect($this->storage->setMultiple($values))->toBeTrue();

    foreach ($values as $key => $value) {
        expect($this->storage->get($key))->toEqual($value);
    }
});

it('deletes multiple values', function (): void {
    $this->storage->set('key9', 'value9');
    $this->storage->set('key10', 'value10');
    expect($this->storage->deleteMultiple(['key9', 'key10']))->toBeTrue();
    expect($this->storage->has('key9'))->toBeFalse();
    expect($this->storage->has('key10'))->toBeFalse();
});

it('checks whether a key exists', function (): void {
    $this->storage->set('key11', 'value11');
    expect($this->storage->has('key11'))->toBeTrue();
    expect($this->storage->has('non_existing_key'))->toBeFalse();
});

it('increments a value', function (): void {
    expect($this->storage->increment('key12', 10))->toEqual(10);
    expect($this->storage->increment('key12', 10))->toEqual(20);
});

it('decrements a value', function (): void {
    $this->storage->set('key13', 20);
    expect($this->storage->decrement('key13', 10))->toEqual(10);
});

it('gets the info of a key', function (): void {
    $this->storage->set('key14', 'value14');
    $info = $this->storage->getInfo('key14');

    expect($info)->toHaveKey('value');
    expect($info['value'] ?? null)->toEqual('value14');
});

it('remembers a value', function (): void {
    $value = $this->storage->remember('key1', fn (): string => 'value1', 1);

    expect($value)->toEqual('value1');
});