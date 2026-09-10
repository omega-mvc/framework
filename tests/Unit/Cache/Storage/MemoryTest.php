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

use DateInterval;
use DateTime;
use Omega\Cache\Storage\MemoryStorage;

use function time;

covers(MemoryStorage::class);

beforeEach(function (): void {
    $this->storage = new MemoryStorage(['ttl' => 3600]);
});

it('sets and gets a value', function (): void {
    expect($this->storage->set('key1', 'value1'))->toBeTrue();
    expect($this->storage->get('key1'))->toEqual('value1');
});

it('gets the default value when the key is not found', function (): void {
    expect($this->storage->get('non_existing_key', 'default'))->toEqual('default');
});

it('sets a value with a ttl and expires it without sleeping', function (): void {
    $key   = 'key2';
    $value = 'value2';

    expect($this->storage->set($key, $value, 1))->toBeTrue();
    expect($this->storage->get($key))->toEqual($value);

    $past = DateInterval::createFromDateString('-1 second');
    $this->storage->set($key, $value, $past);

    expect($this->storage->get($key))->toBeNull();
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
    expect($this->storage->setMultiple(['key7' => 'value7', 'key8' => 'value8']))->toBeTrue();
    expect($this->storage->get('key7'))->toEqual('value7');
    expect($this->storage->get('key8'))->toEqual('value8');
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
    $this->storage->increment('key13', 20);
    expect($this->storage->decrement('key13', 10))->toEqual(10);
});

it('gets the info of a key', function (): void {
    $this->storage->set('key14', 'value14');
    $info = $this->storage->getInfo('key14');

    expect($info)->toHaveKey('value');
    expect($info['value'] ?? null)->toEqual('value14');
});

it('calculates the expiration timestamp', function (): void {
    $time = time();

    $expired = (fn () => $this->{'calculateExpirationTimestamp'}(null))->call($this->storage);
    $this->assertIsInt($expired);
    expect($expired)->toBeGreaterThanOrEqual($time);

    $expired = (fn () => $this->{'calculateExpirationTimestamp'}(time()))->call($this->storage);
    $this->assertIsInt($expired);
    expect($expired)->toBeGreaterThanOrEqual($time);

    $expired = (
        fn () => $this->{'calculateExpirationTimestamp'}(DateInterval::createFromDateString('1 day'))
    )->call($this->storage);
    $this->assertIsInt($expired);
    expect($expired)->toBeGreaterThanOrEqual($time);

    $expired = (fn () => $this->{'calculateExpirationTimestamp'}(new DateTime()))->call($this->storage);
    $this->assertIsInt($expired);
    expect($expired)->toBeGreaterThanOrEqual($time);
});

it('reports whether an item is expired', function (): void {
    $expired = (fn () => $this->{'isExpired'}(time() + 2))->call($this->storage);

    $this->assertIsBool($expired);
    expect($expired)->toBeFalse();
});

it('creates a float mtime', function (): void {
    $mtime = (fn () => $this->{'createMtime'}())->call($this->storage);

    $this->assertIsFloat($mtime);
});

it('remembers a value', function (): void {
    $value = $this->storage->remember('key1', fn (): string => 'value1', 1);

    expect($value)->toEqual('value1');
});