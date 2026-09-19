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

use Omega\Session\Storage\NativeStorage;

use function expect;
use function preg_match;
use function session_destroy;
use function session_id;
use function session_name;
use function session_status;

use const PHP_SESSION_ACTIVE;
use const PHP_SESSION_NONE;

covers(NativeStorage::class);

beforeEach(function (): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    session_name('PHPSESSID');
    session_id('');

    $_SESSION = [];

    $this->storage = new NativeStorage();
});

it('writes and reads session data', function (): void {
    expect($this->storage->write('abc', '{"foo":"bar"}'))->toBeTrue();
    expect($this->storage->read('abc'))->toBe('{"foo":"bar"}');
});

it('stores session data under the default session key', function (): void {
    $this->storage->write('abc', '{"foo":"bar"}');

    expect($_SESSION['omega_session'])->toBe('{"foo":"bar"}');
});

it('stores session data under the configured prefix', function (): void {
    $storage = new NativeStorage(['prefix' => 'app']);
    $storage->write('abc', 'data');

    expect($_SESSION['app_session'])->toBe('data');
});

it('sets a custom session name when configured', function (): void {
    $storage = new NativeStorage(['name' => 'CUSTOM_NAME']);

    expect(session_name())->toBe('CUSTOM_NAME');
});

it('keeps the default session name when not configured', function (): void {
    $storage = new NativeStorage();

    expect(session_name())->toBe('PHPSESSID');
});

it('returns false when reading a missing session', function (): void {
    expect($this->storage->read('missing'))->toBeFalse();
});

it('returns false when the stored value is not a string', function (): void {
    $this->storage->write('abc', 'x');
    $_SESSION['omega_session'] = ['nested' => true];

    expect($this->storage->read('abc'))->toBeFalse();
});

it('returns false when the stored value is an empty string', function (): void {
    $this->storage->write('abc', '');

    expect($this->storage->read('abc'))->toBeFalse();
});

it('returns false for an empty value read while the session is inactive', function (): void {
    $this->storage->write('abc', '');
    $this->storage->close();

    expect($this->storage->read('abc'))->toBeFalse();
});

it('writes into an already active session', function (): void {
    $this->storage->write('abc', 'first');

    expect($this->storage->write('def', 'second'))->toBeTrue();
    expect($_SESSION['omega_session'])->toBe('second');
});

it('destroys an active session and clears its data', function (): void {
    $this->storage->write('abc', '{"foo":"bar"}');

    expect($this->storage->destroy('abc'))->toBeTrue();
    expect($this->storage->read('abc'))->toBeFalse();
});

it('destroys a session starting from an inactive state', function (): void {
    expect($this->storage->destroy('never-started'))->toBeTrue();
    expect(session_status())->toBe(PHP_SESSION_NONE);
});

it('garbage collection reports zero deleted entries', function (): void {
    expect($this->storage->gc(3600))->toBe(0);
});

it('creates a 32-character hexadecimal session id', function (): void {
    $id = $this->storage->createId();

    expect(preg_match('/^[0-9a-f]{32}$/', $id))->toBe(1);
    expect($this->storage->createId())->not->toBe($id);
});

it('closes an active session and persists its data', function (): void {
    $this->storage->write('abc', '{"foo":"bar"}');

    expect($this->storage->close())->toBeTrue();
    expect(session_status())->toBe(PHP_SESSION_NONE);
    expect($this->storage->read('abc'))->toBe('{"foo":"bar"}');
});

it('closes cleanly when no session is active', function (): void {
    expect($this->storage->close())->toBeTrue();
});