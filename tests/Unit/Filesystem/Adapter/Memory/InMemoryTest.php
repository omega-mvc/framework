<?php

declare(strict_types=1);

namespace Tests\Filesystem\Adapter\Memory;

use Omega\Filesystem\Adapter\Memory\InMemory;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(InMemory::class)]
final class InMemoryTest extends TestCase
{
    private InMemory $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new InMemory([
            'file1.txt' => 'Hello World',
            'file2.txt' => [
                'content' => 'Foo Bar',
                'mtime'   => 1000,
            ],
        ]);
    }

    public function testReadsAnExistingFile(): void
    {
        $this->assertSame('Hello World', $this->adapter->read('file1.txt'));
    }

    public function testReadsFalseForNonExistentFile(): void
    {
        $this->assertFalse($this->adapter->read('missing.txt'));
    }

    public function testWritesAFileAndReturnsByteCount(): void
    {
        $bytes = $this->adapter->write('new.txt', 'test');
        $this->assertSame(4, $bytes);
        $this->assertSame('test', $this->adapter->read('new.txt'));
    }

    public function testChecksFileExistence(): void
    {
        $this->assertTrue($this->adapter->exists('file1.txt'));
        $this->assertFalse($this->adapter->exists('missing.txt'));
    }

    public function testListsAllKeys(): void
    {
        $keys = $this->adapter->keys();
        $this->assertSame(['file1.txt', 'file2.txt'], $keys);
    }

    public function testReturnsModificationTimeForExistingFile(): void
    {
        $this->assertSame(1000, $this->adapter->mtime('file2.txt'));
    }

    public function testReturnsFalseMtimeForNonExistentFile(): void
    {
        $this->assertFalse($this->adapter->mtime('missing.txt'));
    }

    public function testDeletesAFile(): void
    {
        $this->assertTrue($this->adapter->delete('file1.txt'));
        $this->assertFalse($this->adapter->exists('file1.txt'));
    }

    public function testAlwaysReturnsFalseForIsDirectory(): void
    {
        $this->assertFalse($this->adapter->isDirectory('file1.txt'));
    }

    public function testRenamesAnExistingFile(): void
    {
        $this->assertTrue($this->adapter->rename('file1.txt', 'renamed.txt'));
        $this->assertTrue($this->adapter->exists('renamed.txt'));
        $this->assertFalse($this->adapter->exists('file1.txt'));
    }

    public function testReturnsFalseWhenRenamingNonExistentFile(): void
    {
        $this->assertFalse($this->adapter->rename('missing.txt', 'new.txt'));
    }

    public function testReturnsMimeTypeForAFile(): void
    {
        $mimeType = $this->adapter->mimeType('file1.txt');
        $this->assertNotEmpty($mimeType);
    }

    public function testConstructsWithNoFiles(): void
    {
        $adapter = new InMemory();
        $this->assertSame([], $adapter->keys());
    }

    public function testSetFilesReplacesAllFiles(): void
    {
        $this->adapter->setFiles(['replaced.txt' => 'new content']);
        $this->assertSame(['replaced.txt'], $this->adapter->keys());
        $this->assertSame('new content', $this->adapter->read('replaced.txt'));
    }

    public function testSetFileAddsASingleFile(): void
    {
        $this->adapter->setFile('added.txt', 'added content');
        $this->assertSame('added content', $this->adapter->read('added.txt'));
        $this->assertTrue($this->adapter->exists('added.txt'));
    }

    public function testSetFileWithNullContentStoresEmptyString(): void
    {
        $this->adapter->setFile('empty.txt');
        $this->assertSame('', $this->adapter->read('empty.txt'));
    }
}
