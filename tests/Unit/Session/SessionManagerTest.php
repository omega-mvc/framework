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
use ReflectionProperty;

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

it('returns true when the session is already started', function (): void {
    expect($this->manager->start())->toBeTrue();
});

it('clears flash with no flashed keys', function (): void {
    $this->manager->clearFlash();

    expect($this->manager->hasFlash('anything'))->toBeFalse();
});

it('clears a single flash key', function (): void {
    $this->manager->flash('a', 1);

    $this->manager->clearFlash();

    expect($this->manager->hasFlash('a'))->toBeFalse();
    expect($this->manager->has('a'))->toBeFalse();
});

it('removes the matching key from the new flash slice', function (): void {
    $this->manager->flash('a', 1);
    $this->manager->now('a', 2);

    expect($this->manager->hasFlash('a'))->toBeTrue();
    expect($this->manager->getFlash('a'))->toBe(2);

    $this->manager->save();

    $next = new SessionManager('array', $this->storage);
    $next->setId('test-session-id');
    $next->start();

    expect($next->hasFlash('a'))->toBeFalse();
    expect($next->getFlash('a'))->toBeNull();
});

it('keeps unrelated new flash keys when flashing now', function (): void {
    $this->manager->flash('a', 1);
    $this->manager->flash('b', 3);
    $this->manager->now('a', 2);

    $this->manager->save();

    $next = new SessionManager('array', $this->storage);
    $next->setId('test-session-id');
    $next->start();

    expect($next->hasFlash('a'))->toBeFalse();
    expect($next->hasFlash('b'))->toBeTrue();
});

it('filters bag data to string keys', function (): void {
    $this->manager->put('cart', ['item' => 'sku-1', 7 => 'ignored']);

    $bag = $this->manager->bag('cart');

    expect($bag->get('item'))->toBe('sku-1');
    expect($bag->get('7'))->toBeNull();
    expect($this->manager->get('cart'))->toEqual(['item' => 'sku-1']);
});

it('does not regenerate a session without an id', function (): void {
    $manager = new SessionManager('array', $this->storage);

    expect($manager->regenerate())->toBeFalse();
});

it('loads session data without flash information', function (): void {
    $this->storage->write('seed-id', '{"user":"alice","theme":"dark"}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->get('user'))->toBe('alice');
    expect($manager->get('theme'))->toBe('dark');
    expect($manager->has('_flash'))->toBeFalse();
});

it('drops non-string keys from loaded session data', function (): void {
    $this->storage->write('seed-id', '[10,20]');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->all())->toBeEmpty();
});

it('resets the data when the stored payload is not a json object', function (): void {
    $this->storage->write('seed-id', 'null');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->all())->toBeEmpty();
});

it('restores multiple flash keys from the stored payload', function (): void {
    $this->storage->write('seed-id', '{"a":"x","_flash":{"new":["n","m","k"],"old":[]}}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->get('a'))->toBe('x');
    expect($manager->has('_flash'))->toBeFalse();
    expect($manager->hasFlash('n'))->toBeTrue();
    expect($manager->hasFlash('m'))->toBeTrue();
    expect($manager->hasFlash('k'))->toBeTrue();
});

it('resolves a closure-backed driver lazily', function (): void {
    $this->manager->setDriver('array', fn (): ArrayStorage => new ArrayStorage());

    $driver = $this->manager->getDriver();

    expect($driver)->toBeInstanceOf(ArrayStorage::class);
    expect($this->manager->getDriver())->toBe($driver);
});

it('filters many new flash keys when flashing now', function (): void {
    $this->manager->flash('a', 1);
    $this->manager->flash('b', 2);
    $this->manager->flash('c', 3);
    $this->manager->flash('d', 4);
    $this->manager->now('b', 9);

    $this->manager->save();

    $next = new SessionManager('array', $this->storage);
    $next->setId('test-session-id');
    $next->start();

    expect($next->hasFlash('a'))->toBeTrue();
    expect($next->hasFlash('b'))->toBeFalse();
    expect($next->hasFlash('c'))->toBeTrue();
    expect($next->hasFlash('d'))->toBeTrue();
});

it('filters matching and duplicated keys when flashing now', function (): void {
    $this->manager->flash('a', 1);
    $this->manager->flash('b', 2);
    $this->manager->now('a', 9);
    $this->manager->now('a', 10);

    expect($this->manager->getFlash('a'))->toBe(10);
    expect($this->manager->getFlash('b'))->toBe(2);

    $this->manager->save();

    $next = new SessionManager('array', $this->storage);
    $next->setId('test-session-id');
    $next->start();

    expect($next->hasFlash('b'))->toBeTrue();
});

it('clears a flashed key moved to the old slice', function (): void {
    $this->manager->now('o', 1);

    $this->manager->clearFlash();

    expect($this->manager->hasFlash('o'))->toBeFalse();
    expect($this->manager->has('o'))->toBeFalse();
});

it('clears duplicated flash keys across slices', function (): void {
    $this->manager->flash('a', 1);
    $this->manager->now('a', 2);

    $this->manager->clearFlash();

    expect($this->manager->hasFlash('a'))->toBeFalse();
    expect($this->manager->has('a'))->toBeFalse();
});

it('clears multiple flash keys spread across both slices', function (): void {
    $this->manager->flash('a', 1);
    $this->manager->flash('b', 2);
    $this->manager->now('c', 3);
    $this->manager->now('d', 4);

    $this->manager->clearFlash();

    foreach (['a', 'b', 'c', 'd'] as $key) {
        expect($this->manager->hasFlash($key))->toBeFalse();
        expect($this->manager->has($key))->toBeFalse();
    }
});

it('clears flash data repeatedly', function (): void {
    $this->manager->flash('a', 1);
    $this->manager->clearFlash();
    $this->manager->clearFlash();

    expect($this->manager->hasFlash('a'))->toBeFalse();
});

it('starts a bag from scalar session data', function (): void {
    $this->manager->put('cart', 'not-an-array');

    $bag = $this->manager->bag('cart');

    expect($bag->getName())->toBe('cart');
    expect($this->manager->get('cart'))->toEqual([]);
});

it('starts a bag from an empty array', function (): void {
    $this->manager->put('cart', []);

    $bag = $this->manager->bag('cart');

    expect($bag->all())->toEqual([]);
});

it('starts a bag from a single string key', function (): void {
    $this->manager->put('cart', ['item' => 'x']);

    $bag = $this->manager->bag('cart');

    expect($bag->get('item'))->toBe('x');
});

it('starts a bag from three string keys', function (): void {
    $this->manager->put('cart', ['a' => 1, 'b' => 2, 'c' => 3]);

    $bag = $this->manager->bag('cart');

    expect($bag->all())->toEqual(['a' => 1, 'b' => 2, 'c' => 3]);
});

it('starts a bag from four string keys and drops numeric ones', function (): void {
    $this->manager->put('cart', [0 => 'zero', 1 => 'one', 'a' => 1, 'b' => 2]);

    $bag = $this->manager->bag('cart');

    expect($bag->all())->toEqual(['a' => 1, 'b' => 2]);
});

it('starts a bag from several string keys', function (): void {
    $this->manager->put('cart', [
        'a' => 1,
        'b' => 2,
        'c' => 3,
        'd' => 4,
        'e' => 5,
    ]);

    $bag = $this->manager->bag('cart');

    expect($bag->get('e'))->toBe(5);
});

it('destroys the session after a save and restart cycle', function (): void {
    $this->manager->save();
    $this->manager->start();

    expect($this->manager->destroy())->toBeTrue();
    expect($this->manager->getId())->toBeNull();
});

it('does not destroy the session twice', function (): void {
    expect($this->manager->destroy())->toBeTrue();
    expect($this->manager->destroy())->toBeFalse();
});

it('saves the session twice across restarts', function (): void {
    $this->manager->put('a', 1);
    $this->manager->save();

    $this->manager->start();
    $this->manager->save();

    expect($this->storage->read('test-session-id'))->not->toBeFalse();
});

it('does not save after the session was destroyed', function (): void {
    $this->manager->destroy();
    $this->manager->save();

    expect($this->storage->read('test-session-id'))->toBeFalse();
});

it('loads no data when the stored payload is an empty string', function (): void {
    $this->storage->write('seed-id', '');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->all())->toBeEmpty();
});

it('loads a single session key', function (): void {
    $this->storage->write('seed-id', '{"user":"alice"}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->get('user'))->toBe('alice');
});

it('loads three session keys without flash information', function (): void {
    $this->storage->write('seed-id', '{"a":1,"b":2,"c":3}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->all())->toEqual(['a' => 1, 'b' => 2, 'c' => 3]);
});

it('loads five session keys without flash information', function (): void {
    $this->storage->write('seed-id', '{"a":1,"b":2,"c":3,"d":4,"e":5}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->get('a'))->toBe(1);
    expect($manager->get('e'))->toBe(5);
});

it('restores a flash slice alongside several session keys', function (): void {
    $this->storage->write('seed-id', '{"a":1,"b":2,"c":3,"d":4,"e":5,"_flash":{"new":["x"],"old":[]}}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->get('a'))->toBe(1);
    expect($manager->get('e'))->toBe(5);
    expect($manager->hasFlash('x'))->toBeTrue();
});

it('restores a flash slice when the payload contains only flash data', function (): void {
    $this->storage->write('seed-id', '{"_flash":{"new":["x"],"old":[]}}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->hasFlash('x'))->toBeTrue();
    expect($manager->has('_flash'))->toBeFalse();
});

it('restores an empty flash slice alongside session keys', function (): void {
    $this->storage->write('seed-id', '{"a":1,"_flash":{"new":[],"old":[]}}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->get('a'))->toBe(1);
    expect($manager->hasFlash('a'))->toBeFalse();
});

it('restores a single new flash key from the stored payload', function (): void {
    $this->storage->write('seed-id', '{"_flash":{"new":["single"],"old":[]}}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->hasFlash('single'))->toBeTrue();
});

it('expires a single old flash key from the stored payload', function (): void {
    $this->storage->write('seed-id', '{"_flash":{"new":[],"old":["oldkey"]}}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->hasFlash('oldkey'))->toBeFalse();

    $manager->save();

    $next = new SessionManager('array', $this->storage);
    $next->setId('seed-id');
    $next->start();

    expect($next->hasFlash('oldkey'))->toBeFalse();
});

it('restores flash keys ignoring non string values', function (): void {
    $this->storage->write('seed-id', '{"_flash":{"new":["x",7,false,"y"],"old":["z"]}}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->hasFlash('x'))->toBeTrue();
    expect($manager->hasFlash('y'))->toBeTrue();
    expect($manager->hasFlash('z'))->toBeFalse();
});

it('loads a json object containing an array value', function (): void {
    $this->storage->write('seed-id', '{"tags":["a","b"],"user":"u"}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->get('tags'))->toEqual(['a', 'b']);
});

it('expires multiple old flash keys on the next request', function (): void {
    $this->manager->now('a', 1);
    $this->manager->now('b', 2);
    $this->manager->save();

    $next = new SessionManager('array', $this->storage);
    $next->setId('test-session-id');
    $next->start();

    expect($next->hasFlash('a'))->toBeFalse();
    expect($next->hasFlash('b'))->toBeFalse();
});

it('ignores non string flash keys when restoring the new slice', function (): void {
    $this->storage->write('seed-id', '{"_flash":{"new":[7],"old":[]}}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->hasFlash('7'))->toBeFalse();
    expect($manager->hasFlash('_flash'))->toBeFalse();
});

it('loads no data when the stored payload is an empty object', function (): void {
    $this->storage->write('seed-id', '{}');

    $manager = new SessionManager('array', $this->storage);
    $manager->setId('seed-id');
    $manager->start();

    expect($manager->all())->toBeEmpty();
});

it('starts a bag from a single numeric key', function (): void {
    $this->manager->put('cart', [7]);

    $bag = $this->manager->bag('cart');

    expect($bag->all())->toEqual([]);
});

it('does not destroy a session whose id has been cleared', function (): void {
    $property = new ReflectionProperty(SessionManager::class, 'id');
    $property->setValue($this->manager, null);

    expect($this->manager->destroy())->toBeFalse();
});

it('does not save a session whose id has been cleared', function (): void {
    $property = new ReflectionProperty(SessionManager::class, 'id');
    $property->setValue($this->manager, null);

    $this->manager->save();

    expect($this->storage->read('test-session-id'))->toBeFalse();
});
