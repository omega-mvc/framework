<?php

declare(strict_types=1);

namespace Tests\Archive;

use Omega\Archive\ZipAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

use function copy;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(ZipAdapter::class)]
final class ZipAdapterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/omega-archive-zip-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        self::zipRemoveDirectory($this->tempDir);
    }

    public function testChecksMembershipViaExists(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->assertTrue($adapter->exists('hello.txt'));
        $this->assertFalse($adapter->exists('missing.txt'));
    }

    public function testReturnsTheStoredContentOfAMemberOnRead(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->assertSame('hello zip payload', $adapter->read('hello.txt'));
    }

    public function testThrowsWhenTheMemberDoesNotExistOnRead(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to read the key');

        $adapter->read('nope.txt');
    }

    public function testWriteAddsAMemberAndReturnsItsLength(): void
    {
        $file = $this->tempDir . '/written.zip';
        copy(__DIR__ . '/fixtures/archive/sample.zip', $file);

        $adapter = new ZipAdapter($file);

        $length = $adapter->write('new.txt', 'abcde');

        $this->assertSame(5, $length);

        $adapter->close();

        $reopened = new ZipAdapter($file);

        $this->assertSame('abcde', $reopened->read('new.txt'));
    }

    public function testDeleteRemovesAMember(): void
    {
        $file = $this->tempDir . '/written.zip';
        copy(__DIR__ . '/fixtures/archive/sample.zip', $file);

        $adapter = new ZipAdapter($file);

        $this->assertTrue($adapter->delete('hello.txt'));

        $adapter->close();

        $reopened = new ZipAdapter($file);

        $this->assertFalse($reopened->exists('hello.txt'));
    }

    public function testReturnsTheMemberNamesOnKeys(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->assertSame(['hello.txt'], $adapter->keys());
    }

    public function testReturnsAnEmptyArrayForAnEmptyArchiveOnKeys(): void
    {
        $file = $this->tempDir . '/empty.zip';

        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::CREATE);
        $zip->close();

        $adapter = new ZipAdapter($file);

        $this->assertSame([], $adapter->keys());
    }

    public function testIsDirectoryDistinguishesDirectoriesFromFiles(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->assertFalse($adapter->isDirectory('hello.txt'));
        $this->assertTrue($adapter->isDirectory('some/dir/'));
    }

    public function testReturnsAnIntegerTimestampOnMtime(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->assertIsInt($adapter->mtime('hello.txt'));
    }

    public function testReturnsFalseForAMissingMemberOnMtime(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->assertFalse($adapter->mtime('missing.txt'));
    }

    public function testRenamePersistsTheChangeToDisk(): void
    {
        $file = $this->tempDir . '/rename.zip';
        copy(__DIR__ . '/fixtures/archive/sample.zip', $file);

        $adapter = new ZipAdapter($file);

        $this->assertTrue($adapter->rename('hello.txt', 'renamed.txt'));

        $adapter->close();

        $reopened = new ZipAdapter($file);

        $this->assertSame(['renamed.txt'], $reopened->keys());
        $this->assertSame('hello zip payload', $reopened->read('renamed.txt'));
        $this->assertFalse($reopened->exists('hello.txt'));
    }

    public function testThrowsWhenTheSourceMemberDoesNotExistOnRename(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('source file');

        $adapter->rename('missing.txt', 'renamed.txt');
    }

    public function testThrowsWhenTheTargetMemberAlreadyExistsOnRename(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('target file');

        $adapter->rename('hello.txt', 'hello.txt');
    }

    public function testThrowsWhenTheFileIsNotAValidZipArchiveInTheConstructor(): void
    {
        $file = $this->tempDir . '/corrupt.zip';
        file_put_contents($file, 'PK');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to open the ZIP file');

        new ZipAdapter($file);
    }

    public function testThrowsWhenTheArchiveEntersAnInvalidStateOnKeys(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $adapter->delete('missing.txt');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not open or is invalid');

        $adapter->keys();
    }

    public function testThrowsWhenTheUnderlyingRenameOperationFails(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to rename');

        $adapter->rename('hello.txt', 'foo/');
    }

    public function testCanReOpenAnAdapterWithADifferentArchiveOnOpen(): void
    {
        $file = $this->tempDir . '/other.zip';
        copy(__DIR__ . '/fixtures/archive/sample.zip', $file);

        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $adapter->open($file);

        $this->assertSame('hello zip payload', $adapter->read('hello.txt'));
    }

    public function testThrowsWhenTheFileIsNotAValidZipArchiveOnOpen(): void
    {
        $file = $this->tempDir . '/invalid.txt';
        file_put_contents($file, 'not a zip archive content at all');

        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to open the ZIP file');

        $adapter->open($file);
    }

    public function testCloseIsCallableWithoutThrowing(): void
    {
        $adapter = new ZipAdapter(__DIR__ . '/fixtures/archive/sample.zip');

        $adapter->close();

        $this->assertTrue(file_exists(__DIR__ . '/fixtures/archive/sample.zip'));
    }

    private static function zipRemoveDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = slash($directory . '/' . $item);

            if (is_dir($path)) {
                self::zipRemoveDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
