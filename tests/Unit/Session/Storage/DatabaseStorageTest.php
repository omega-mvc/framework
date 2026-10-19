<?php

declare(strict_types=1);

namespace Tests\Session\Storage;

use Omega\Session\Storage\DatabaseStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\TestCase;

use function preg_match;

#[CoversClass(DatabaseStorage::class)]
final class DatabaseStorageTest extends TestCase
{
    public function testReadsSessionDataFromTheDatabase(): void
    {
        $pdo     = (new ScriptedConnection())->whenQueryContains('SELECT data', [['data' => '{"foo":"bar"}']]);
        $storage = new DatabaseStorage($pdo);

        $this->assertSame('{"foo":"bar"}', $storage->read('abc123'));
    }

    public function testReturnsFalseWhenNoSessionRowMatches(): void
    {
        $pdo     = (new ScriptedConnection())->whenQueryContains('SELECT data', []);
        $storage = new DatabaseStorage($pdo);

        $this->assertFalse($storage->read('abc123'));
    }

    public function testReturnsFalseWhenTheStoredPayloadIsNotAString(): void
    {
        $pdo     = (new ScriptedConnection())->whenQueryContains('SELECT data', [['data' => 123]]);
        $storage = new DatabaseStorage($pdo);

        $this->assertFalse($storage->read('abc123'));
    }

    public function testReturnsFalseWhenTheStoredPayloadIsEmpty(): void
    {
        $pdo     = (new ScriptedConnection())->whenQueryContains('SELECT data', [['data' => '']]);
        $storage = new DatabaseStorage($pdo);

        $this->assertFalse($storage->read('abc123'));
    }

    public function testWritesSessionDataByReplacingTheRow(): void
    {
        $pdo     = new ScriptedConnection();
        $storage = new DatabaseStorage($pdo);

        $this->assertTrue($storage->write('abc123', '{"foo":"bar"}'));

        $this->assertCount(2, $pdo->queries);
        $this->assertStringContainsString('DELETE FROM sessions', $pdo->queries[0]);
        $this->assertStringContainsString('INSERT INTO sessions', $pdo->queries[1]);
    }

    public function testDestroysASessionRow(): void
    {
        $pdo     = new ScriptedConnection();
        $storage = new DatabaseStorage($pdo);

        $this->assertTrue($storage->destroy('abc123'));

        $this->assertStringContainsString('DELETE FROM sessions WHERE id = :id', $pdo->queries[0]);
    }

    public function testGarbageCollectsExpiredSessions(): void
    {
        $pdo     = new ScriptedConnection();
        $storage = new DatabaseStorage($pdo);

        $this->assertSame(1, $storage->gc(7200));

        $this->assertStringContainsString('DELETE FROM sessions WHERE last_activity < :expiry', $pdo->queries[0]);
    }

    public function testCreatesA32CharacterHexadecimalSessionId(): void
    {
        $pdo     = new ScriptedConnection();
        $storage = new DatabaseStorage($pdo);
        $id      = $storage->createId();

        $this->assertSame(1, preg_match('/^[0-9a-f]{32}$/', $id));
        $this->assertNotSame($id, $storage->createId());
    }

    public function testClosesTheStorageBackend(): void
    {
        $pdo     = new ScriptedConnection();
        $storage = new DatabaseStorage($pdo);

        $this->assertTrue($storage->close());
    }
}
