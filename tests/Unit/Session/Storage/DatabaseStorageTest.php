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

use Omega\Session\Storage\DatabaseStorage;
use Tests\Console\Commands\Fixtures\ScriptedConnection;

use function expect;
use function preg_match;

covers(DatabaseStorage::class);

it('reads session data from the database', function (): void {
    $pdo     = (new ScriptedConnection())->whenQueryContains('SELECT data', [['data' => '{"foo":"bar"}']]);
    $storage = new DatabaseStorage($pdo);

    expect($storage->read('abc123'))->toBe('{"foo":"bar"}');
});

it('returns false when no session row matches', function (): void {
    $pdo     = (new ScriptedConnection())->whenQueryContains('SELECT data', []);
    $storage = new DatabaseStorage($pdo);

    expect($storage->read('abc123'))->toBeFalse();
});

it('writes session data by replacing the row', function (): void {
    $pdo     = new ScriptedConnection();
    $storage = new DatabaseStorage($pdo);

    expect($storage->write('abc123', '{"foo":"bar"}'))->toBeTrue();

    expect($pdo->queries)->toHaveCount(2);
    expect($pdo->queries[0])->toContain('DELETE FROM sessions');
    expect($pdo->queries[1])->toContain('INSERT INTO sessions');
});

it('destroys a session row', function (): void {
    $pdo     = new ScriptedConnection();
    $storage = new DatabaseStorage($pdo);

    expect($storage->destroy('abc123'))->toBeTrue();

    expect($pdo->queries[0])->toContain('DELETE FROM sessions WHERE id = :id');
});

it('garbage collects expired sessions', function (): void {
    $pdo     = new ScriptedConnection();
    $storage = new DatabaseStorage($pdo);

    expect($storage->gc(7200))->toBe(1);

    expect($pdo->queries[0])->toContain('DELETE FROM sessions WHERE last_activity < :expiry');
});

it('creates a 32-character hexadecimal session id', function (): void {
    $pdo     = new ScriptedConnection();
    $storage = new DatabaseStorage($pdo);
    $id      = $storage->createId();

    expect(preg_match('/^[0-9a-f]{32}$/', $id))->toBe(1);
    expect($storage->createId())->not->toBe($id);
});

it('closes the storage backend', function (): void {
    $pdo     = new ScriptedConnection();
    $storage = new DatabaseStorage($pdo);

    expect($storage->close())->toBeTrue();
});