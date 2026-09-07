<?php

declare(strict_types=1);

namespace Tests\Cache\Storage;

use Omega\Cache\Storage\ApcuStorage;

covers(ApcuStorage::class);

it('sets and gets a value from the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    expect($storage->set('key1', 'value1'))->toBeTrue();
    expect($storage->get('key1'))->toEqual('value1');
});

it('gets the default value when the key is not found', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    expect($storage->get('non_existing_key', 'default'))->toEqual('default');
});

it('sets a value with a ttl', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    expect($storage->set('key2', 'value2', 1))->toBeTrue();
});

it('deletes a value from the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    $storage->set('key3', 'value3');
    expect($storage->delete('key3'))->toBeTrue();
    expect($storage->has('key3'))->toBeFalse();
});

it('returns false when deleting a non-existing key', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    expect($storage->delete('non_existing_key'))->toBeFalse();
});

it('clears all values from the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    $storage->set('key4', 'value4');
    expect($storage->clear())->toBeTrue();
    expect($storage->has('key4'))->toBeFalse();
});

it('gets multiple values from the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    $storage->set('key5', 'value5');
    $storage->set('key6', 'value6');
    $result = $storage->getMultiple(['key5', 'key6', 'non_existing_key'], 'default');

    expect($result)->toEqual([
        'key5'            => 'value5',
        'key6'            => 'value6',
        'non_existing_key' => 'default',
    ]);
});

it('sets multiple values to the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    expect($storage->setMultiple(['key7' => 'value7', 'key8' => 'value8']))->toBeTrue();
    expect($storage->get('key7'))->toEqual('value7');
    expect($storage->get('key8'))->toEqual('value8');
});

it('deletes multiple values from the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    $storage->set('key9', 'value9');
    $storage->set('key10', 'value10');
    expect($storage->deleteMultiple(['key9', 'key10']))->toBeTrue();
    expect($storage->has('key9'))->toBeFalse();
    expect($storage->has('key10'))->toBeFalse();
});

it('checks whether a key exists in the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    $storage->set('key11', 'value11');
    expect($storage->has('key11'))->toBeTrue();
    expect($storage->has('non_existing_key'))->toBeFalse();
});

it('increments a value in the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    expect($storage->increment('key12', 10))->toEqual(10);
    expect($storage->increment('key12', 10))->toEqual(20);
});

it('decrements a value in the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    $storage->increment('key13', 20);
    expect($storage->decrement('key13', 10))->toEqual(10);
});

it('gets the info of a key from the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    $storage->set('key14', 'value14');
    $info = $storage->getInfo('key14');

    expect($info)->toHaveKey('value');
    expect($info['value'] ?? null)->toEqual('value14');
    expect($info)->toHaveKey('timestamp');
    expect($info)->toHaveKey('mtime');
});

it('remembers a value in the apcu storage', function (): void {
    $storage = cache_apcu_storage();

    if (null === $storage) {
        $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');

    }

    $value = $storage->remember('key1', fn (): string => 'value1', 1);
    expect($value)->toEqual('value1');

    // second call should get from cache
    $value = $storage->remember('key1', fn (): string => 'value2', 1);
    expect($value)->toEqual('value1');
});

function cache_apcu_storage(): ?ApcuStorage
{
    if (!ApcuStorage::isSupported()) {
        return null;
    }

    $storage = new ApcuStorage(['ttl' => 3600, 'prefix' => 'test_']);
    $storage->clear();

    return $storage;
}