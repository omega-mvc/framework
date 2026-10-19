<?php

declare(strict_types=1);

namespace Tests\Filesystem\Adapter\Local;

use InvalidArgumentException;
use Omega\Filesystem\Adapter\Local\Local;
use Omega\Filesystem\Stream\StreamMode;
use Omega\Filesystem\Util\Checksum;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(Local::class)]
final class LocalTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/omega_test_' . uniqid();
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->dir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo) {
                    $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                }
            }
            rmdir($this->dir);
        }

        parent::tearDown();
    }

    public function testConstructsWithExistingDirectory(): void
    {
        $adapter = new Local($this->dir);
        $this->assertTrue($adapter->isDirectory('.'));
    }

    public function testThrowsWhenDirectoryDoesNotExistAndCreateIsFalse(): void
    {
        $adapter = new Local($this->dir);
        rmdir($this->dir);

        $this->expectException(RuntimeException::class);

        $adapter->read('file.txt');
    }

    public function testCreatesDirectoryLazilyWhenCreateIsTrue(): void
    {
        $newDir  = $this->dir . '/newdir';
        $adapter = new Local($newDir, true);
        $adapter->write('test.txt', 'content');
        $this->assertTrue(is_dir($newDir));
    }

    public function testWritesAndReadsAFile(): void
    {
        $adapter = new Local($this->dir, true);
        $bytes   = $adapter->write('test.txt', 'Hello');
        $this->assertSame(5, $bytes);
        $this->assertSame('Hello', $adapter->read('test.txt'));
    }

    public function testReturnsFalseWhenReadingDirectory(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('file.txt', 'content');
        $this->assertFalse($adapter->read('.'));
    }

    public function testChecksFileExistence(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('test.txt', 'content');
        $this->assertTrue($adapter->exists('test.txt'));
        $this->assertFalse($adapter->exists('missing.txt'));
    }

    public function testListsKeys(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('b.txt', 'b');
        $adapter->write('a.txt', 'a');
        $keys = $adapter->keys();
        $this->assertSame(['a.txt', 'b.txt'], $keys);
    }

    public function testReturnsModificationTime(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('test.txt', 'content');
        $mtime = $adapter->mtime('test.txt');
        $this->assertIsInt($mtime);
    }

    public function testDeletesAFile(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('test.txt', 'content');
        $this->assertTrue($adapter->delete('test.txt'));
        $this->assertFalse($adapter->exists('test.txt'));
    }

    public function testReturnsFalseWhenDeletingNonExistentFile(): void
    {
        $adapter = new Local($this->dir, true);
        $this->assertFalse($adapter->delete('missing.txt'));
    }

    public function testRenamesAFile(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('old.txt', 'content');
        $this->assertTrue($adapter->rename('old.txt', 'new.txt'));
        $this->assertTrue($adapter->exists('new.txt'));
        $this->assertFalse($adapter->exists('old.txt'));
    }

    public function testChecksIfPathIsADirectory(): void
    {
        $adapter = new Local($this->dir, true);
        $this->assertTrue($adapter->isDirectory('.'));
        $adapter->write('file.txt', 'content');
        $this->assertFalse($adapter->isDirectory('file.txt'));
    }

    public function testComputesChecksum(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('test.txt', 'hello');
        $this->assertSame(Checksum::fromContent('hello'), $adapter->checksum('test.txt'));
    }

    public function testComputesSize(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('test.txt', 'hello');
        $this->assertSame(5, $adapter->size('test.txt'));
    }

    public function testReturnsMimeType(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('test.txt', 'hello');
        $mimeType = $adapter->mimeType('test.txt');
        $this->assertNotEmpty($mimeType);
    }

    public function testComputesKeyFromPath(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('subdir/file.txt', 'content');
        $key = $adapter->computeKey($this->dir . '/subdir/file.txt');
        $this->assertSame('subdir/file.txt', $key);
    }

    public function testDeletesADirectoryRecursively(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('subdir/file.txt', 'content');
        $this->assertTrue($adapter->delete('subdir'));
        $this->assertFalse($adapter->isDirectory('subdir'));
    }

    public function testThrowsWhenDeletingRootDirectory(): void
    {
        $adapter = new Local($this->dir, true);

        $this->expectException(InvalidArgumentException::class);

        $adapter->delete('.');
    }

    public function testCreatesAStream(): void
    {
        $adapter = new Local($this->dir, true);
        $adapter->write('test.txt', 'content');
        $stream = $adapter->createStream('test.txt');
        $this->assertTrue($stream->open(new StreamMode('r')));
        $this->assertSame('content', $stream->read(7));
    }
}
