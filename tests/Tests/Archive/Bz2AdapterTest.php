<?php

declare(strict_types=1);

namespace Tests\Archive;

use Omega\Archive\Bz2Adapter;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

use function bzcompress;
use function chmod;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function is_readable;
use function is_string;
use function is_writable;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function restore_error_handler;
use function scandir;
use function set_error_handler;
use function str_contains;
use function strlen;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(Bz2Adapter::class)]
final class Bz2AdapterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/omega-archive-bz2-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        self::bz2RemoveDirectory($this->tempDir);
    }

    public function testStoresTheBz2FilePathInTheConstructor(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/data.bz2');

        $this->assertSame($this->tempDir . '/data.bz2', self::readProperty($adapter));
    }

    public function testThrowsWhenTheFileDoesNotExistOnOpen(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/missing.bz2');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $adapter->open($this->tempDir . '/nope.bz2');
    }

    public function testThrowsWhenTheFileIsNotReadableOnOpen(): void
    {
        $file = $this->tempDir . '/unreadable.bz2';
        file_put_contents($file, 'data');
        chmod($file, 0000);

        if (is_readable($file)) {
            $this->markTestSkipped('File permissions do not allow simulating an unreadable file.');
        }

        $adapter = new Bz2Adapter($file);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not readable');

        $adapter->open($file);

        chmod($file, 0644);
    }

    public function testAcceptsAnExistingReadableFileOnOpen(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/other.bz2');

        $adapter->open(__DIR__ . '/fixtures/archive/sample.bz2');

        $this->assertSame(__DIR__ . '/fixtures/archive/sample.bz2', self::readProperty($adapter));
    }

    public function testCloseIsANoOpForTheBz2Adapter(): void
    {
        $adapter = new Bz2Adapter(__DIR__ . '/fixtures/archive/sample.bz2');

        $adapter->close();

        $this->assertTrue(file_exists(__DIR__ . '/fixtures/archive/sample.bz2'));
    }

    public function testReturnsTheDecompressedContentOnRead(): void
    {
        $adapter = new Bz2Adapter(__DIR__ . '/fixtures/archive/sample.bz2');

        $this->assertSame('hello bz2 payload', $adapter->read(''));
    }

    public function testReturnsFalseForCorruptedContentOnRead(): void
    {
        $file = $this->tempDir . '/corrupt.bz2';
        file_put_contents($file, 'this is not valid bzip2 data ----');

        $adapter = new Bz2Adapter($file);

        $this->assertFalse($adapter->read(''));
    }

    public function testCompressesTheContentAndReturnsTheCompressedLengthOnWrite(): void
    {
        $file = $this->tempDir . '/written.bz2';
        $adapter = new Bz2Adapter($file);

        $length = $adapter->write('', 'hello write payload');

        $compressed = bzcompress('hello write payload');

        if (!is_string($compressed)) {
            $this->fail('Expected bzcompress to return a compressed string.');
        }

        $this->assertSame(strlen($compressed), $length);
        $this->assertSame('hello write payload', $adapter->read(''));
    }

    public function testDeleteIsNotSupportedAndReturnsFalse(): void
    {
        $adapter = new Bz2Adapter(__DIR__ . '/fixtures/archive/sample.bz2');

        $this->assertFalse($adapter->delete(''));
    }

    public function testExistsReflectsWhetherTheBz2FileExistsOnDisk(): void
    {
        $adapter = new Bz2Adapter(__DIR__ . '/fixtures/archive/sample.bz2');

        $this->assertTrue($adapter->exists(''));
        $this->assertFalse((new Bz2Adapter($this->tempDir . '/missing.bz2'))->exists(''));
    }

    public function testKeysIsNotSupportedAndReturnsAnEmptyArray(): void
    {
        $adapter = new Bz2Adapter(__DIR__ . '/fixtures/archive/sample.bz2');

        $this->assertSame([], $adapter->keys());
    }

    public function testIsDirectoryIsNotSupportedAndReturnsFalse(): void
    {
        $adapter = new Bz2Adapter(__DIR__ . '/fixtures/archive/sample.bz2');

        $this->assertFalse($adapter->isDirectory(''));
    }

    public function testReturnsAnIntegerTimestampOnMtime(): void
    {
        $adapter = new Bz2Adapter(__DIR__ . '/fixtures/archive/sample.bz2');

        $this->assertIsInt($adapter->mtime(''));
    }

    public function testThrowsWhenTheFileDoesNotExistOnMtime(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/missing.bz2');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('modification time');

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'filemtime');
        });

        try {
            $adapter->mtime('');
        } finally {
            restore_error_handler();
        }
    }

    public function testRenameMovesAFileSuccessfully(): void
    {
        $source = $this->tempDir . '/source.bz2';
        $target = $this->tempDir . '/target.bz2';
        file_put_contents($source, 'payload');

        $adapter = new Bz2Adapter($source);

        $this->assertTrue($adapter->rename($source, $target));
        $this->assertFalse(file_exists($source));
        $this->assertTrue(file_exists($target));
    }

    public function testThrowsWhenTheSourceFileDoesNotExistOnRename(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/x.bz2');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Source file');

        $adapter->rename($this->tempDir . '/missing.bz2', $this->tempDir . '/target.bz2');
    }

    public function testThrowsWhenTheTargetFileAlreadyExistsOnRename(): void
    {
        $source = $this->tempDir . '/source.bz2';
        $target = $this->tempDir . '/target.bz2';
        file_put_contents($source, 'payload');
        file_put_contents($target, 'payload');

        $adapter = new Bz2Adapter($source);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Target file');

        $adapter->rename($source, $target);
    }

    public function testThrowsWhenTheSourceFileIsNotReadableOnRename(): void
    {
        $source = $this->tempDir . '/source.bz2';
        $target = $this->tempDir . '/target.bz2';
        file_put_contents($source, 'payload');
        chmod($source, 0000);

        if (is_readable($source)) {
            $this->markTestSkipped('File permissions do not allow simulating an unreadable source.');
        }

        $adapter = new Bz2Adapter($source);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not readable');

        $adapter->rename($source, $target);

        chmod($source, 0644);
    }

    public function testThrowsWhenTheTargetDirectoryIsNotWritableOnRename(): void
    {
        $source    = $this->tempDir . '/source.bz2';
        $target    = $this->tempDir . '/locked/target.bz2';
        $lockedDir = $this->tempDir . '/locked';
        mkdir($lockedDir, 0000, true);
        file_put_contents($source, 'payload');

        if (is_writable($lockedDir)) {
            $this->markTestSkipped('Directory permissions do not allow simulating a locked directory.');
        }

        $adapter = new Bz2Adapter($source);

        try {
            $adapter->rename($source, $target);
            $this->fail('Expected RuntimeException to be thrown');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($lockedDir, $e->getMessage());
        }
    }

    public function testThrowsWhenTheTargetPathIsEmptyOnRename(): void
    {
        $source = $this->tempDir . '/source.bz2';
        file_put_contents($source, 'payload');

        $adapter = new Bz2Adapter($source);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot write to the directory of the target file');

        $adapter->rename($source, '');
    }

    public function testThrowsWhenTheFileDoesNotExistOnRead(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/missing.bz2');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to read the file');

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'file_get_contents');
        });

        try {
            $adapter->read('');
        } finally {
            restore_error_handler();
        }
    }

    public function testThrowsWhenTheTargetPathIsNotWritableOnWrite(): void
    {
        $target = $this->tempDir . '/a-directory';
        mkdir($target, 0777);

        $adapter = new Bz2Adapter($target);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write to the file');

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'file_put_contents');
        });

        try {
            $adapter->write('', 'payload');
        } finally {
            restore_error_handler();
        }
    }

    public function testThrowsWhenTheUnderlyingRenameOperationFails(): void
    {
        $source = $this->tempDir . '/source.bz2';
        file_put_contents($source, 'payload');

        $adapter = new Bz2Adapter($source);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to rename');

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'rename');
        });

        try {
            $adapter->rename($source, $source . '/child');
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Returns the bz2File property value from the adapter.
     */
    private static function readProperty(Bz2Adapter $adapter): string
    {
        $reflection = new \ReflectionProperty(Bz2Adapter::class, 'bz2File');
        $value      = $reflection->getValue($adapter);

        if (!is_string($value)) {
            throw new \UnexpectedValueException('Expected the bz2File property to be a string.');
        }

        return $value;
    }

    /**
     * Recursively removes a directory and all of its contents.
     */
    private static function bz2RemoveDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        @chmod($directory, 0755);
        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = slash($directory . '/' . $item);

            if (is_dir($path)) {
                self::bz2RemoveDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
