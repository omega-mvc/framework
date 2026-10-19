<?php

declare(strict_types=1);

namespace Tests\Filesystem;

use InvalidArgumentException;
use Omega\Filesystem\Adapter\Memory\InMemory;
use Omega\Filesystem\Exception\FileAlreadyExistsException;
use Omega\Filesystem\Exception\FileNotFoundException;
use Omega\Filesystem\Exception\UnexpectedFileExcption;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\Stream\InMemoryBuffer;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Filesystem::class)]
final class FilesystemTest extends TestCase
{
    private InMemory $adapter;

    private Filesystem $fs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new InMemory();
        $this->fs      = new Filesystem($this->adapter);
    }

    public function testReturnsTheAdapter(): void
    {
        $this->assertSame($this->adapter, $this->fs->getAdapter());
    }

    public function testChecksIfKeyExists(): void
    {
        $this->adapter->write('file.txt', 'content');
        $this->assertTrue($this->fs->has('file.txt'));
        $this->assertFalse($this->fs->has('missing.txt'));
    }

    public function testThrowsOnEmptyKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Object path is empty.');

        $this->fs->has('');
    }

    public function testWritesANewFileAndReturnsByteCount(): void
    {
        $bytes = $this->fs->write('new.txt', 'hello');
        $this->assertSame(5, $bytes);
        $this->assertSame('hello', $this->fs->read('new.txt'));
    }

    public function testThrowsWhenWritingExistingFileWithoutOverwrite(): void
    {
        $this->fs->write('file.txt', 'content');

        $this->expectException(FileAlreadyExistsException::class);

        $this->fs->write('file.txt', 'new content');
    }

    public function testOverwritesExistingFileWhenOverwriteIsTrue(): void
    {
        $this->fs->write('file.txt', 'old');
        $this->fs->write('file.txt', 'new', true);
        $this->assertSame('new', $this->fs->read('file.txt'));
    }

    public function testReadsAnExistingFile(): void
    {
        $this->fs->write('file.txt', 'content');
        $this->assertSame('content', $this->fs->read('file.txt'));
    }

    public function testThrowsWhenReadingNonExistentFile(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->fs->read('missing.txt');
    }

    public function testDeletesAnExistingFile(): void
    {
        $this->fs->write('file.txt', 'content');
        $this->assertTrue($this->fs->delete('file.txt'));
        $this->assertFalse($this->fs->has('file.txt'));
    }

    public function testThrowsWhenDeletingNonExistentFile(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->fs->delete('missing.txt');
    }

    public function testGetsAFileObject(): void
    {
        $this->fs->write('file.txt', 'content');
        $file = $this->fs->get('file.txt');
        $this->assertSame('file.txt', $file->getKey());
    }

    public function testGetsAFileWithCreateFlag(): void
    {
        $file = $this->fs->get('new.txt', true);
        $this->assertSame('new.txt', $file->getKey());
    }

    public function testThrowsWhenGettingNonExistentFileWithoutCreate(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->fs->get('missing.txt');
    }

    public function testGetsAFileFromRegister(): void
    {
        $file1 = $this->fs->get('file.txt', true);
        $file2 = $this->fs->get('file.txt', true);
        $this->assertSame($file1, $file2);
    }

    public function testReturnsKeys(): void
    {
        $this->fs->write('a.txt', 'a');
        $this->fs->write('b.txt', 'b');
        $keys = $this->fs->keys();
        $this->assertCount(2, $keys);
    }

    public function testListsKeysWithPrefix(): void
    {
        $this->fs->write('dir/file.txt', 'content');
        $this->fs->write('other.txt', 'other');
        $result = $this->fs->listKeys('dir/');
        $this->assertContains('dir/file.txt', $result['keys']);
    }

    public function testReturnsModificationTime(): void
    {
        $this->fs->write('file.txt', 'content');
        $mtime = $this->fs->mtime('file.txt');
        $this->assertGreaterThanOrEqual(0, $mtime);
    }

    public function testThrowsWhenGettingMtimeOfNonExistentFile(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->fs->mtime('missing.txt');
    }

    public function testComputesChecksum(): void
    {
        $this->fs->write('file.txt', 'hello');
        $checksum = $this->fs->checksum('file.txt');
        $this->assertSame(md5('hello'), $checksum);
    }

    public function testComputesSize(): void
    {
        $this->fs->write('file.txt', 'hello');
        $this->assertSame(5, $this->fs->size('file.txt'));
    }

    public function testCreatesAStreamForNonStreamAdapter(): void
    {
        $this->fs->write('file.txt', 'content');
        $stream = $this->fs->createStream('file.txt');
        $this->assertInstanceOf(InMemoryBuffer::class, $stream);
    }

    public function testCreatesAFileObject(): void
    {
        $file = $this->fs->createFile('file.txt');
        $this->assertSame('file.txt', $file->getKey());
    }

    public function testReturnsMimeTypeForFileWithMimeProvider(): void
    {
        $this->fs->write('file.txt', 'hello');
        $mimeType = $this->fs->mimeType('file.txt');
        $this->assertNotEmpty($mimeType);
    }

    public function testThrowsWhenGettingMimeTypeOfNonExistentFile(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->fs->mimeType('missing.txt');
    }

    public function testChecksIfKeyIsADirectory(): void
    {
        $this->assertFalse($this->fs->isDirectory('anything'));
    }

    public function testClearsFileRegister(): void
    {
        $file1 = $this->fs->get('file.txt', true);
        $this->fs->clearFileRegister();
        $file2 = $this->fs->createFile('file.txt');
        $this->assertNotSame($file1, $file2);
    }

    public function testRemovesFromRegister(): void
    {
        $file1 = $this->fs->get('file.txt', true);
        $this->fs->removeFromRegister('file.txt');
        $file2 = $this->fs->createFile('file.txt');
        $this->assertNotSame($file1, $file2);
    }

    public function testRenameMovesAFile(): void
    {
        $this->fs->write('old.txt', 'content');
        $this->assertTrue($this->fs->rename('old.txt', 'new.txt'));
        $this->assertTrue($this->fs->has('new.txt'));
        $this->assertFalse($this->fs->has('old.txt'));
    }

    public function testRenameThrowsWhenTargetExists(): void
    {
        $this->fs->write('old.txt', 'content');
        $this->fs->write('new.txt', 'other');

        $this->expectException(UnexpectedFileExcption::class);

        $this->fs->rename('old.txt', 'new.txt');
    }

    public function testRenameThrowsWhenSourceNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->fs->rename('missing.txt', 'new.txt');
    }
}
