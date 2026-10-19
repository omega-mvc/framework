<?php

declare(strict_types=1);

namespace Tests\Session\Storage;

use Omega\Session\Storage\ArrayStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function preg_match;

#[CoversClass(ArrayStorage::class)]
final class ArrayStorageTest extends TestCase
{
    private ArrayStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new ArrayStorage();
    }

    public function testReturnsFalseWhenReadingAMissingSession(): void
    {
        $this->assertFalse($this->storage->read('missing-id'));
    }

    public function testWritesAndReadsSessionData(): void
    {
        $this->assertTrue($this->storage->write('abc', '{"foo":"bar"}'));
        $this->assertSame('{"foo":"bar"}', $this->storage->read('abc'));
    }

    public function testOverwritesExistingSessionData(): void
    {
        $this->storage->write('abc', '{"foo":"bar"}');
        $this->storage->write('abc', '{"baz":"qux"}');

        $this->assertSame('{"baz":"qux"}', $this->storage->read('abc'));
    }

    public function testDestroysASessionEntry(): void
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
}
