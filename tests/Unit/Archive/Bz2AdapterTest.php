<?php

/**
 * Part of Omega - Tests\Archive Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Archive;

use Omega\Archive\Bz2Adapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Tests\FixturesPathTrait;

use function bzcompress;
use function chmod;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_readable;
use function is_string;
use function is_writable;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function restore_error_handler;
use function set_error_handler;
use function strlen;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Class Bz2AdapterTest
 *
 * This test suite verifies the behavior of the {@see Bz2Adapter} bzip2 adapter:
 * open, read, write, exists, mtime, rename and the unsupported archive operations.
 *
 * @category   Tests
 * @package    Archive
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Bz2Adapter::class)]
final class Bz2AdapterTest extends TestCase
{
    use FixturesPathTrait;

    /** @var string Temporary directory used to isolate bz2 file operations. */
    private string $tempDir;

    /**
     * Sets up the environment before each test method.
     *
     * Creates an isolated temporary directory inside the system temp path.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/omega-archive-bz2-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    /**
     * Tears down the environment after each test method.
     *
     * Recursively removes the temporary directory created in {@see setUp}.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    /**
     * Recursively removes a directory and all of its contents.
     *
     * @param string $directory The directory to remove.
     * @return void
     */
    private function removeDirectory(string $directory): void
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
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }

    /**
     * Returns the absolute path to the sample bz2 fixture.
     *
     * @return string The fixture path.
     */
    private function samplePath(): string
    {
        return $this->setFixturePath('/fixtures/archive/sample.bz2');
    }

    /**
     * Tests that the adapter can be constructed with a target file path.
     *
     * @return void
     */
    public function testConstructStoresBz2File(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/data.bz2');

        $this->assertSame($this->tempDir . '/data.bz2', $this->readProperty($adapter));
    }

    /**
     * Returns the bz2File property value from the adapter.
     *
     * @param Bz2Adapter $adapter The adapter instance.
     * @return string The stored bz2 file path.
     */
    private function readProperty(Bz2Adapter $adapter): string
    {
        $reflection = new ReflectionProperty(Bz2Adapter::class, 'bz2File');
        $value = $reflection->getValue($adapter);

        if (!is_string($value)) {
            $this->fail('Expected the bz2File property to be a string.');
        }

        return $value;
    }

    /**
     * Tests that open throws when the file does not exist.
     *
     * @return void
     */
    public function testOpenThrowsWhenFileDoesNotExist(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/missing.bz2');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $adapter->open($this->tempDir . '/nope.bz2');
    }

    /**
     * Tests that open throws when the file is not readable.
     *
     * @return void
     */
    public function testOpenThrowsWhenFileNotReadable(): void
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

    /**
     * Tests that open accepts an existing readable file.
     *
     * @return void
     */
    public function testOpenAcceptsExistingReadableFile(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/other.bz2');

        $adapter->open($this->samplePath());

        $this->assertSame($this->samplePath(), $this->readProperty($adapter));
    }

    /**
     * Tests that close is a no-op for the bz2 adapter.
     *
     * @return void
     */
    public function testCloseIsNoOp(): void
    {
        $adapter = new Bz2Adapter($this->samplePath());

        $adapter->close();

        $this->assertFileExists($this->samplePath());
    }

    /**
     * Tests that read returns the decompressed payload of the sample file.
     *
     * @return void
     */
    public function testReadReturnsDecompressedContent(): void
    {
        $adapter = new Bz2Adapter($this->samplePath());

        $this->assertSame('hello bz2 payload', $adapter->read(''));
    }

    /**
     * Tests that read returns false for corrupted content.
     *
     * @return void
     */
    public function testReadReturnsFalseForCorruptedContent(): void
    {
        $file = $this->tempDir . '/corrupt.bz2';
        file_put_contents($file, 'this is not valid bzip2 data ----');

        $adapter = new Bz2Adapter($file);

        $this->assertFalse($adapter->read(''));
    }

    /**
     * Tests that write compresses the content and returns the length of the compressed bytes.
     *
     * @return void
     */
    public function testWriteReturnsCompressedLength(): void
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

    /**
     * Tests that delete is unsupported and returns false.
     *
     * @return void
     */
    public function testDeleteIsNotSupported(): void
    {
        $adapter = new Bz2Adapter($this->samplePath());

        $this->assertFalse($adapter->delete(''));
    }

    /**
     * Tests that exists reflects whether the bz2 file exists on disk.
     *
     * @return void
     */
    public function testExistsReflectsFilePresence(): void
    {
        $adapter = new Bz2Adapter($this->samplePath());

        $this->assertTrue($adapter->exists(''));
        $this->assertFalse((new Bz2Adapter($this->tempDir . '/missing.bz2'))->exists(''));
    }

    /**
     * Tests that keys is unsupported and returns an empty array.
     *
     * @return void
     */
    public function testKeysIsNotSupported(): void
    {
        $adapter = new Bz2Adapter($this->samplePath());

        $this->assertSame([], $adapter->keys());
    }

    /**
     * Tests that isDirectory is unsupported and returns false.
     *
     * @return void
     */
    public function testIsDirectoryIsNotSupported(): void
    {
        $adapter = new Bz2Adapter($this->samplePath());

        $this->assertFalse($adapter->isDirectory(''));
    }

    /**
     * Tests that mtime returns an integer timestamp for an existing file.
     *
     * @return void
     */
    public function testMtimeReturnsTimestamp(): void
    {
        $adapter = new Bz2Adapter($this->samplePath());

        $this->assertIsInt($adapter->mtime(''));
    }

    /**
     * Tests that mtime throws when the file does not exist.
     *
     * @return void
     */
    public function testMtimeThrowsWhenFileDoesNotExist(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/missing.bz2');

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'filemtime');
        });

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('modification time');

            $adapter->mtime('');
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Tests that rename moves a file successfully.
     *
     * @return void
     */
    public function testRenameMovesFile(): void
    {
        $source = $this->tempDir . '/source.bz2';
        $target = $this->tempDir . '/target.bz2';
        file_put_contents($source, 'payload');

        $adapter = new Bz2Adapter($source);

        $this->assertTrue($adapter->rename($source, $target));
        $this->assertFalse(file_exists($source));
        $this->assertTrue(file_exists($target));
    }

    /**
     * Tests that rename throws when the source file does not exist.
     *
     * @return void
     */
    public function testRenameThrowsWhenSourceMissing(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/x.bz2');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Source file');

        $adapter->rename($this->tempDir . '/missing.bz2', $this->tempDir . '/target.bz2');
    }

    /**
     * Tests that rename throws when the target file already exists.
     *
     * @return void
     */
    public function testRenameThrowsWhenTargetExists(): void
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

    /**
     * Tests that rename throws when the source file is not readable.
     *
     * @return void
     */
    public function testRenameThrowsWhenSourceNotReadable(): void
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

    /**
     * Tests that rename throws when the target directory is not writable.
     *
     * @return void
     */
    public function testRenameThrowsWhenTargetDirectoryNotWritable(): void
    {
        $source = $this->tempDir . '/source.bz2';
        $target = $this->tempDir . '/locked/target.bz2';
        mkdir($this->tempDir . '/locked', 0000, true);
        file_put_contents($source, 'payload');

        if (is_writable($this->tempDir . '/locked')) {
            $this->markTestSkipped('Directory permissions do not allow simulating a locked directory.');
        }

        $adapter = new Bz2Adapter($source);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(dirname($target));

        $adapter->rename($source, $target);
    }

    /**
     * Tests that rename throws when the target path is empty.
     *
     * @return void
     */
    public function testRenameThrowsWhenTargetIsEmpty(): void
    {
        $source = $this->tempDir . '/source.bz2';
        file_put_contents($source, 'payload');

        $adapter = new Bz2Adapter($source);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot write to the directory of the target file');

        $adapter->rename($source, '');
    }

    /**
     * Tests that read throws when the file does not exist.
     *
     * @return void
     */
    public function testReadThrowsWhenFileDoesNotExist(): void
    {
        $adapter = new Bz2Adapter($this->tempDir . '/missing.bz2');

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'file_get_contents');
        });

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Failed to read the file');

            $adapter->read('');
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Tests that write throws when the target path is not writable.
     *
     * @return void
     */
    public function testWriteThrowsWhenTargetNotWritable(): void
    {
        $target = $this->tempDir . '/a-directory';
        mkdir($target, 0777);

        $adapter = new Bz2Adapter($target);

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'file_put_contents');
        });

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Failed to write to the file');

            $adapter->write('', 'payload');
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Tests that rename throws when the underlying rename operation fails.
     *
     * @return void
     */
    public function testRenameThrowsWhenRenameFails(): void
    {
        $source = $this->tempDir . '/source.bz2';
        file_put_contents($source, 'payload');

        $adapter = new Bz2Adapter($source);

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'rename');
        });

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Failed to rename');

            $adapter->rename($source, $source . '/child');
        } finally {
            restore_error_handler();
        }
    }
}
