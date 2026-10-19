<?php

declare(strict_types=1);

namespace Tests\Session\Storage;

use Omega\Cache\Storage\MemoryStorage;
use Omega\Session\Storage\CacheStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function preg_match;

#[CoversClass(CacheStorage::class)]
final class CacheStorageTest extends TestCase
{
    private MemoryStorage $cache;

    private CacheStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache   = new MemoryStorage(['ttl' => 3600]);
        $this->storage = new CacheStorage($this->cache);
    }

    public function testWritesAndReadsSessionDataThroughTheCache(): void
    {
        $this->assertTrue($this->storage->write('abc', '{"foo":"bar"}'));
        $this->assertSame('{"foo":"bar"}', $this->storage->read('abc'));
    }

    public function testReturnsFalseWhenReadingAMissingSession(): void
    {
        $this->assertFalse($this->storage->read('missing-id'));
    }

    public function testReturnsFalseWhenTheCachedValueIsNotAString(): void
    {
        $this->cache->set('session_nonstring', ['data' => 'not-a-string'], 3600);

        $this->assertFalse($this->storage->read('nonstring'));
    }

    public function testDestroysASessionEntryAndRemovesItFromTheCache(): void
    {
        $this->storage->write('abc', '{"foo":"bar"}');

        $this->assertTrue($this->storage->destroy('abc'));
        $this->assertFalse($this->storage->read('abc'));
    }

    public function testGarbageCollectionReportsZeroDeletedEntries(): void
    {
        $this->assertSame(0, $this->storage->gc(3600));
    }

    public function testCreatesA32CharacterHexadecimalSessionId(): void
    {
        $id = $this->storage->createId();

        $this->assertSame(1, preg_match('/^[0-9a-f]{32}$/', $id));
        $this->assertNotSame($id, $this->storage->createId());
    }

    public function testClosesTheStorageBackend(): void
    {
        $this->assertTrue($this->storage->close());
    }

    public function testStoresSessionEntriesUnderTheConfiguredPrefix(): void
    {
        $this->storage->write('abc', '{"foo":"bar"}');

        $this->assertTrue($this->cache->has('session_abc'));
    }
}
