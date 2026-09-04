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

use Omega\Archive\ZipAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\FixturesPathTrait;
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

/**
 * Class ZipAdapterTest
 *
 * This test suite verifies the behavior of the {@see ZipAdapter} ZIP adapter:
 * construct, open, close, read, write, delete, exists, keys, isDirectory,
 * mtime and rename operations.
 *
 * @category   Tests
 * @package    Archive
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(ZipAdapter::class)]
final class ZipAdapterTest extends TestCase
{
    use FixturesPathTrait;

    /** @var string Temporary directory used to isolate ZIP file operations. */
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
        $this->tempDir = sys_get_temp_dir() . '/omega-archive-zip-' . uniqid();
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
     * Returns the absolute path to the sample ZIP fixture.
     *
     * @return string The fixture path.
     */
    private function samplePath(): string
    {
        return $this->setFixturePath('/fixtures/archive/sample.zip');
    }

    /**
     * Tests that a missing source file is handled correctly through exists.
     *
     * @return void
     */
    public function testExistsChecksMembership(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $this->assertTrue($adapter->exists('hello.txt'));
        $this->assertFalse($adapter->exists('missing.txt'));
    }

    /**
     * Tests that read returns the stored content of a member.
     *
     * @return void
     */
    public function testReadReturnsMemberContent(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $this->assertSame('hello zip payload', $adapter->read('hello.txt'));
    }

    /**
     * Tests that read throws when the member does not exist.
     *
     * @return void
     */
    public function testReadThrowsWhenKeyMissing(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to read the key');

        $adapter->read('nope.txt');
    }

    /**
     * Tests that write adds a member and returns its length.
     *
     * @return void
     */
    public function testWriteAddsMember(): void
    {
        $file = $this->tempDir . '/written.zip';
        copy($this->samplePath(), $file);

        $adapter = new ZipAdapter($file);

        $length = $adapter->write('new.txt', 'abcde');

        $this->assertSame(5, $length);

        $adapter->close();

        $reopened = new ZipAdapter($file);

        $this->assertSame('abcde', $reopened->read('new.txt'));
    }

    /**
     * Tests that delete removes a member.
     *
     * @return void
     */
    public function testDeleteRemovesMember(): void
    {
        $file = $this->tempDir . '/written.zip';
        copy($this->samplePath(), $file);

        $adapter = new ZipAdapter($file);

        $this->assertTrue($adapter->delete('hello.txt'));

        $adapter->close();

        $reopened = new ZipAdapter($file);

        $this->assertFalse($reopened->exists('hello.txt'));
    }

    /**
     * Tests that keys returns the list of member names in the sample archive.
     *
     * @return void
     */
    public function testKeysReturnsMemberNames(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $this->assertSame(['hello.txt'], $adapter->keys());
    }

    /**
     * Tests that keys returns an empty array for an empty archive.
     *
     * @return void
     */
    public function testKeysReturnsEmptyForEmptyArchive(): void
    {
        $file = $this->tempDir . '/empty.zip';

        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::CREATE);
        $zip->close();

        $adapter = new ZipAdapter($file);

        $this->assertSame([], $adapter->keys());
    }

    /**
     * Tests that isDirectory distinguishes directories from files.
     *
     * @return void
     */
    public function testIsDirectoryDetectsDirectories(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $this->assertFalse($adapter->isDirectory('hello.txt'));
        $this->assertTrue($adapter->isDirectory('some/dir/'));
    }

    /**
     * Tests that mtime returns an integer timestamp for an existing member.
     *
     * @return void
     */
    public function testMtimeReturnsTimestamp(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $this->assertIsInt($adapter->mtime('hello.txt'));
    }

    /**
     * Tests that mtime returns false for a missing member.
     *
     * @return void
     */
    public function testMtimeReturnsFalseForMissingMember(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $this->assertFalse($adapter->mtime('missing.txt'));
    }

    /**
     * Tests that rename moves a member and persists the change to disk.
     *
     * @return void
     */
    public function testRenamePersistsToDisk(): void
    {
        $file = $this->tempDir . '/rename.zip';
        copy($this->samplePath(), $file);

        $adapter = new ZipAdapter($file);

        $this->assertTrue($adapter->rename('hello.txt', 'renamed.txt'));

        $adapter->close();

        $reopened = new ZipAdapter($file);

        $this->assertSame(['renamed.txt'], $reopened->keys());
        $this->assertSame('hello zip payload', $reopened->read('renamed.txt'));
        $this->assertFalse($reopened->exists('hello.txt'));
    }

    /**
     * Tests that rename throws when the source member does not exist.
     *
     * @return void
     */
    public function testRenameThrowsWhenSourceMissing(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('source file');

        $adapter->rename('missing.txt', 'renamed.txt');
    }

    /**
     * Tests that rename throws when the target member already exists.
     *
     * @return void
     */
    public function testRenameThrowsWhenTargetExists(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('target file');

        $adapter->rename('hello.txt', 'hello.txt');
    }

    /**
     * Tests that the constructor throws when the file is not a valid ZIP archive.
     *
     * @return void
     */
    public function testConstructThrowsWhenFileIsCorrupt(): void
    {
        $file = $this->tempDir . '/corrupt.zip';
        file_put_contents($file, 'PK');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to open the ZIP file');

        new ZipAdapter($file);
    }

    /**
     * Tests that keys throws when the archive enters an invalid state.
     *
     * @return void
     */
    public function testKeysThrowsWhenArchiveIsInvalid(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $adapter->delete('missing.txt');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not open or is invalid');

        $adapter->keys();
    }

    /**
     * Tests that rename throws when the underlying rename operation fails.
     *
     * @return void
     */
    public function testRenameThrowsWhenRenameFails(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to rename');

        $adapter->rename('hello.txt', 'foo/');
    }

    /**
     * Tests that open can re-open an adapter with a different archive.
     *
     * @return void
     */
    public function testOpenReopensDifferentArchive(): void
    {
        $file = $this->tempDir . '/other.zip';
        copy($this->samplePath(), $file);

        $adapter = new ZipAdapter($this->samplePath());

        $adapter->open($file);

        $this->assertSame('hello zip payload', $adapter->read('hello.txt'));
    }

    /**
     * Tests that open throws when the file is not a valid ZIP archive.
     *
     * @return void
     */
    public function testOpenThrowsWhenInvalidArchive(): void
    {
        $file = $this->tempDir . '/invalid.txt';
        file_put_contents($file, 'not a zip archive content at all');

        $adapter = new ZipAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to open the ZIP file');

        $adapter->open($file);
    }

    /**
     * Tests that close is callable without throwing.
     *
     * @return void
     */
    public function testCloseIsCallable(): void
    {
        $adapter = new ZipAdapter($this->samplePath());

        $adapter->close();

        $this->assertFileExists($this->samplePath());
    }
}
