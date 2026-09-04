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

use Omega\Archive\NativePharEngine;
use Omega\Archive\PharAdapter;
use Omega\Archive\PharEngineInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\FixturesPathTrait;

use function array_keys;
use function copy;
use function is_dir;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function scandir;
use function str_contains;
use function str_ends_with;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Class PharAdapterTest
 *
 * This test suite verifies the behavior of the {@see PharAdapter} PHAR adapter:
 * construct, open, read, exists, keys, isDirectory, mtime, write, delete and rename.
 * Write-capable operations are exercised against an in-memory {@see PharEngineInterface}
 * fake so that they remain covered even when the environment has `phar.readonly=1`.
 *
 * @category   Tests
 * @package    Archive
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(PharAdapter::class)]
#[CoversClass(NativePharEngine::class)]
final class PharAdapterTest extends TestCase
{
    use FixturesPathTrait;

    /** @var string Temporary directory used to isolate PHAR file operations. */
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
        $this->tempDir = sys_get_temp_dir() . '/omega-archive-phar-' . uniqid();
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
     * Returns the absolute path to the sample Phar fixture.
     *
     * @return string The fixture path.
     */
    private function samplePath(): string
    {
        return $this->setFixturePath('/fixtures/archive/sample.phar');
    }

    /**
     * Creates an in-memory {@see PharEngineInterface} fake seeded with the given members.
     *
     * @param array<string,string> $members Holds the initial members keyed by name.
     * @return FakePharEngine The configured fake engine.
     */
    private function makeEngine(array $members = []): FakePharEngine
    {
        return new FakePharEngine($members);
    }

    /**
     * Tests that the constructor accepts an existing Phar archive.
     *
     * @return void
     */
    public function testConstructAcceptsExistingFile(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->assertSame('hello phar payload', $adapter->read('hello.txt'));
    }

    /**
     * Tests that the constructor throws when the file does not exist.
     *
     * @return void
     */
    public function testConstructThrowsWhenFileMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        new PharAdapter($this->tempDir . '/missing.phar');
    }

    /**
     * Tests that open accepts an existing Phar archive.
     *
     * @return void
     */
    public function testOpenAcceptsExistingFile(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $adapter->open($this->samplePath());

        $this->assertSame('hello phar payload', $adapter->read('hello.txt'));
    }

    /**
     * Tests that open throws when the file does not exist.
     *
     * @return void
     */
    public function testOpenThrowsWhenFileMissing(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $adapter->open($this->tempDir . '/missing.phar');
    }

    /**
     * Tests that open throws when the file is not a valid Phar archive.
     *
     * @return void
     */
    public function testOpenThrowsWhenPharCorrupt(): void
    {
        $file = $this->tempDir . '/corrupt.phar';
        file_put_contents($file, 'this is plain text, not a phar archive');

        $adapter = new PharAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to open Phar archive');

        $adapter->open($file);
    }

    /**
     * Tests that close is callable without throwing.
     *
     * @return void
     */
    public function testCloseIsCallable(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $adapter->close();

        $this->assertTrue($adapter->exists('hello.txt'));
    }

    /**
     * Tests that read returns the stored content of a member.
     *
     * @return void
     */
    public function testReadReturnsMemberContent(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->assertSame('hello phar payload', $adapter->read('hello.txt'));
    }

    /**
     * Tests that read throws when the member does not exist.
     *
     * @return void
     */
    public function testReadThrowsWhenKeyMissing(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("does not exist in the Phar archive");

        $adapter->read('nope.txt');
    }

    /**
     * Tests that exists reflects membership in the archive.
     *
     * @return void
     */
    public function testExistsReflectsMembership(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->assertTrue($adapter->exists('hello.txt'));
        $this->assertFalse($adapter->exists('missing.txt'));
    }

    /**
     * Tests that keys returns the stream URIs of the archive members.
     *
     * @return void
     */
    public function testKeysReturnsStreamUris(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $keys = $adapter->keys();

        $this->assertCount(1, $keys);
        $this->assertStringStartsWith('phar://', $keys[0]);
        $this->assertTrue(str_contains($keys[0], 'hello.txt'));
    }

    /**
     * Tests that isDirectory distinguishes directories from files.
     *
     * @return void
     */
    public function testIsDirectoryDetectsDirectories(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->assertFalse($adapter->isDirectory('hello.txt'));
    }

    /**
     * Tests that isDirectory throws when the key does not exist.
     *
     * @return void
     */
    public function testIsDirectoryThrowsWhenKeyMissing(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $adapter->isDirectory('nope.txt');
    }

    /**
     * Tests that mtime returns an integer timestamp for an existing member.
     *
     * @return void
     */
    public function testMtimeReturnsTimestamp(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->assertIsInt($adapter->mtime('hello.txt'));
    }

    /**
     * Tests that mtime throws when the member does not exist.
     *
     * @return void
     */
    public function testMtimeThrowsWhenKeyMissing(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("does not exist");

        $adapter->mtime('nope.txt');
    }

    /**
     * Tests that write adds a member and returns its length.
     *
     * @return void
     */
    public function testWriteAddsMember(): void
    {
        $fake = $this->makeEngine();
        $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

        $length = $adapter->write('new.txt', 'abcde');

        $this->assertSame(5, $length);
        $this->assertSame('abcde', $adapter->read('new.txt'));
        $this->assertSame(['new.txt' => 'abcde'], $fake->state());
    }

    /**
     * Tests that delete removes a member.
     *
     * @return void
     */
    public function testDeleteRemovesMember(): void
    {
        $fake = $this->makeEngine(['hello.txt' => 'hello phar payload']);
        $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

        $this->assertTrue($adapter->delete('hello.txt'));
        $this->assertFalse($adapter->exists('hello.txt'));
    }

    /**
     * Tests that delete throws when the key does not exist.
     *
     * @return void
     */
    public function testDeleteThrowsWhenKeyMissing(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist and cannot be deleted');

        $adapter->delete('nope.txt');
    }

    /**
     * Tests that rename moves a member and removes the source.
     *
     * @return void
     */
    public function testRenameMovesMemberAndRemovesSource(): void
    {
        $fake = $this->makeEngine(['hello.txt' => 'hello phar payload']);
        $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

        $this->assertTrue($adapter->rename('hello.txt', 'renamed.txt'));
        $this->assertFalse($adapter->exists('hello.txt'));
        $this->assertTrue($adapter->exists('renamed.txt'));
        $this->assertSame('hello phar payload', $adapter->read('renamed.txt'));
        $this->assertSame(['renamed.txt' => 'hello phar payload'], $fake->state());
    }

    /**
     * Tests that rename throws when the source member does not exist.
     *
     * @return void
     */
    public function testRenameThrowsWhenSourceMissing(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $adapter->rename('missing.txt', 'renamed.txt');
    }

    /**
     * Tests that rename throws when the target member already exists.
     *
     * @return void
     */
    public function testRenameThrowsWhenTargetExists(): void
    {
        $adapter = new PharAdapter($this->samplePath());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        $adapter->rename('hello.txt', 'hello.txt');
    }

    /**
     * Tests that rename wraps failures from the underlying engine.
     *
     * @return void
     */
    public function testRenameThrowsWhenSourceIsDirectoryMember(): void
    {
        $fake = new FailingPharEngine(['mydir' => '']);
        $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to rename');

        $adapter->rename('mydir', 'renamed');
    }

    /**
     * Tests that rename wraps a read failure for the source member.
     *
     * @return void
     */
    public function testRenameThrowsWhenContentUnreadable(): void
    {
        $fake = new UnreadablePharEngine(['hello.txt' => 'hello phar payload']);
        $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to rename');

        $adapter->rename('hello.txt', 'renamed.txt');
    }
}
