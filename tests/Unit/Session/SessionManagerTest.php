<?php

/**
 * Part of Omega - Tests\Session Package.
 * @link https://omega-mvc.github.io
 * @author Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version 2.0.0
 */

declare(strict_types=1);

namespace Tests\Session;

use Omega\Session\SessionManager;
use Omega\Session\Storage\ArrayStorage;

use function expect;
use function preg_match;

covers(SessionManager::class);

beforeEach(function (): void {
    $this->storage = new ArrayStorage();
    $this->manager = new SessionManager('array', $this->storage);
    $this->manager->setId('test-session-id');
    $this->manager->start();
});

it('starts the session, creating a persisted id and loading data', function (): void {
    $manager = new SessionManager('array', $this->storage);

    expect($manager->start())->toBeTrue();

    $id = $manager->getId();

    expect($id)->not->toBeNull();
    expect(preg_match('/^[0-9a-f]{32}$/', (string) $id))->toBe(1);

    $manager->put('theme', 'dark');
    $manager->save();

    $reloaded = new SessionManager('array', $this->storage);
    $reloaded->setId((string) $id);
    $reloaded->start();

    expect($reloaded->get('theme'))->toBe('dark');
});

it('registers drivers and resolves the default one', function (): void {
    expect($this->manager->getDriver())->toBe($this->storage);

    $driver = new ArrayStorage();

    expect($this->manager->setDriver('cache', $driver))->toBe($this->manager);
});

it('stores, reads, checks, forgets and flushes session data', function (): void {
    expect($this->manager->has('user'))->toBeFalse();

    $this->manager->put('user', 'alice');

    expect($this->manager->has('user'))->toBeTrue();
    expect($this->manager->get('user'))->toBe('alice');
    expect($this->manager->get('missing', 'fallback'))->toBe('fallback');
    expect($this->manager->all())->toEqual(['user' => 'alice']);

    $this->manager->forget('user');

    expect($this->manager->has('user'))->toBeFalse();

    $this->manager->put('a', 1);
    $this->manager->put('b', 2);
    $this->manager->flush();

    expect($this->manager->all())->toBeEmpty();
});

it('exposes flashed values to the following request then expires them', function (): void {
    $this->manager->flash('status', 'ok');

    expect($this->manager->hasFlash('status'))->toBeTrue();
    expect($this->manager->getFlash('status'))->toBe('ok');

    $this->manager->save();

    $next = new SessionManager('array', $this->storage);
    $next->setId('test-session-id');
    $next->start();

    expect($next->hasFlash('status'))->toBeTrue();
    expect($next->getFlash('status'))->toBe('ok');

    $next->save();

    $third = new SessionManager('array', $this->storage);
    $third->setId('test-session-id');
    $third->start();

    expect($third->hasFlash('status'))->toBeFalse();
    expect($third->has('status'))->toBeFalse();
    expect($third->getFlash('status', 'gone'))->toBe('gone');
});

it('flashes a value only for the current request', function (): void {
    $this->manager->now('notice', 'hello');

    expect($this->manager->hasFlash('notice'))->toBeTrue();

    $this->manager->save();

    $next = new SessionManager('array', $this->storage);
    $next->setId('test-session-id');
    $next->start();

    expect($next->hasFlash('notice'))->toBeFalse();
    expect($next->getFlash('notice', 'gone'))->toBe('gone');
});

it('reflash keeps current flash data available', function (): void {
    $this->manager->flash('alert', 'warn');
    $this->manager->reflash();

    expect($this->manager->hasFlash('alert'))->toBeTrue();
    expect($this->manager->getFlash('alert'))->toBe('warn');
});

it('clears all flash data', function (): void {
    $this->manager->flash('a', 1);
    $this->manager->now('b', 2);

    $this->manager->clearFlash();

    expect($this->manager->hasFlash('a'))->toBeFalse();
    expect($this->manager->hasFlash('b'))->toBeFalse();
    expect($this->manager->has('a'))->toBeFalse();
    expect($this->manager->has('b'))->toBeFalse();
});

it('caches session bags that share the manager data', function (): void {
    $first  = $this->manager->bag('cart');
    $second = $this->manager->bag('cart');

    expect($second)->toBe($first);
    expect($first->getName())->toBe('cart');

    $first->put('item', 'sku-1');

    expect($this->manager->get('cart'))->toEqual(['item' => 'sku-1']);
});

it('regenerates the session id while keeping the data', function (): void {
    $this->manager->put('user', 'alice');
    $previous = $this->manager->getId();

    expect($this->manager->regenerate())->toBeTrue();

    expect($this->manager->getId())->not->toBe($previous);
    expect($this->manager->get('user'))->toBe('alice');
});

it('regenerates the session id and destroys the previous storage entry', function (): void {
    $this->manager->put('user', 'alice');
    $previous = (string) $this->manager->getId();
    $this->manager->save();

    expect($this->manager->regenerate(true))->toBeTrue();

    expect($this->storage->read($previous))->toBeFalse();
    expect($this->manager->get('user'))->toBe('alice');
});

it('destroys the session and removes its stored data', function (): void {
    $id = (string) $this->manager->getId();
    $this->manager->getDriver()->write($id, '{"user":"alice"}');
    $this->manager->put('user', 'alice');

    expect($this->manager->destroy())->toBeTrue();

    expect($this->manager->getId())->toBeNull();
    expect($this->manager->get('user'))->toBeNull();
    expect($this->storage->read($id))->toBeFalse();
});

it('does not destroy a session that has not started', function (): void {
    $manager = new SessionManager('array', $this->storage);

    expect($manager->destroy())->toBeFalse();

    $manager->setId('orphan-id');

    expect($manager->destroy())->toBeFalse();
});

it('does not save when the session was never started', function (): void {
    $manager = new SessionManager('array', $this->storage);
    $manager->setId('untouched-id');
    $manager->put('key', 'value');
    $manager->save();

    expect($this->storage->read('untouched-id'))->toBeFalse();
});