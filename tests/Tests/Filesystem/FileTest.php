<?php

declare(strict_types=1);

namespace Tests\Filesystem;

use Omega\Filesystem\Adapter\Memory\InMemory;
use Omega\Filesystem\Exception\FileNotFoundException;
use Omega\Filesystem\File;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\Stream\InMemoryBuffer;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(File::class)]
final class FileTest extends TestCase
{
    private InMemory $adapter;

    private Filesystem $fs;

    private File $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new InMemory([
            'test.txt' => 'Hello World',
        ]);
        $this->fs   = new Filesystem($this->adapter);
        $this->file = $this->fs->get('test.txt');
    }

    public function testReturnsTheKey(): void
    {
        $this->assertSame('test.txt', $this->file->getKey());
    }

    public function testReturnsTheName(): void
    {
        $this->assertSame('test.txt', $this->file->getName());
    }

    public function testSetsAndGetsTheName(): void
    {
        $this->file->setName('renamed.txt');
        $this->assertSame('renamed.txt', $this->file->getName());
    }

    public function testGetsContentLazilyFromFilesystem(): void
    {
        $this->assertSame('Hello World', $this->file->getContent());
    }

    public function testCachesContentAfterFirstRead(): void
    {
        $content1 = $this->file->getContent();
        $this->adapter->write('test.txt', 'Changed');
        $content2 = $this->file->getContent();
        $this->assertSame($content1, $content2);
    }

    public function testGetsSizeFromFilesystem(): void
    {
        $this->assertSame(11, $this->file->getSize());
    }

    public function testSetsSizeDirectly(): void
    {
        $this->file->setSize(100);
        $this->assertSame(100, $this->file->getSize());
    }

    public function testGetsModificationTime(): void
    {
        $mtime = $this->file->getMtime();
        $this->assertGreaterThanOrEqual(0, $mtime);
    }

    public function testSetsContentAndReturnsByteCount(): void
    {
        $bytes = $this->file->setContent('New content');
        $this->assertSame(11, $bytes);
        $this->assertSame('New content', $this->file->getContent());
    }

    public function testChecksExistence(): void
    {
        $this->assertTrue($this->file->exists());
    }

    public function testDeletesFile(): void
    {
        $this->assertTrue($this->file->delete());
        $this->assertFalse($this->file->exists());
    }

    public function testCreatesAStream(): void
    {
        $stream = $this->file->createStream();
        $this->assertInstanceOf(InMemoryBuffer::class, $stream);
    }

    public function testRenamesFile(): void
    {
        $this->file->rename('new-key.txt');
        $this->assertSame('new-key.txt', $this->file->getKey());
        $this->assertTrue($this->fs->has('new-key.txt'));
    }

    public function testReturns0SizeForNonExistentFile(): void
    {
        $file = $this->fs->get('missing.txt', true);
        $this->assertSame(0, $file->getSize());
    }
}
