<?php

/**
 * Part of Omega - Tests\Session\Storage Package.
 * @link https://omega-mvc.github.io
 * @author Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version 2.0.0
 */

declare(strict_types=1);

namespace Tests\Session\Storage;

use Omega\Cache\Storage\MemoryStorage;
use Omega\Session\Storage\CacheStorage;

use function expect;
use function preg_match;

covers(CacheStorage::class);

beforeEach(function (): void {
    $this->cache   = new MemoryStorage(['ttl' => 3600]);
    $this->storage = new CacheStorage($this->cache);
});

it('writes and reads session data through the cache', function (): void {
    expect($this->storage->write('abc', '{"foo":"bar"}'))->toBeTrue();
    expect($this->storage->read('abc'))->toBe('{"foo":"bar"}');
});

it('returns false when reading a missing session', function (): void {
    expect($this->storage->read('missing-id'))->toBeFalse();
});

it('destroys a session entry and removes it from the cache', function (): void {
    $this->storage->write('abc', '{"foo":"bar"}');

    expect($this->storage->destroy('abc'))->toBeTrue();
    expect($this->storage->read('abc'))->toBeFalse();
});

it('garbage collection reports zero deleted entries', function (): void {
    expect($this->storage->gc(3600))->toBe(0);
});

it('creates a 32-character hexadecimal session id', function (): void {
    $id = $this->storage->createId();

    expect(preg_match('/^[0-9a-f]{32}$/', $id))->toBe(1);
    expect($this->storage->createId())->not->toBe($id);
});

it('closes the storage backend', function (): void {
    expect($this->storage->close())->toBeTrue();
});

it('stores session entries under the configured prefix', function (): void {
    $this->storage->write('abc', '{"foo":"bar"}');

    expect($this->cache->has('session_abc'))->toBeTrue();
});