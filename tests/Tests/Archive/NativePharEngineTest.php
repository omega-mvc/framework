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
use PharData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function is_dir;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Class NativePharEngineTest
 *
 * This suite exercises the {@see NativePharEngine} against a real, writable
 * {@see PharData} (tar) archive. Unlike {@see Phar}, a {@see PharData} can be
 * written even when `phar.readonly=1`, so the native `write()` and `delete()`
 * paths are covered here without requiring a disabled read-only restriction.
 *
 * @category   Tests
 * @package    Archive
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(NativePharEngine::class)]
final class NativePharEngineTest extends TestCase
{
    /** @var string Temporary directory used to isolate archive file operations. */
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
        $this->tempDir = sys_get_temp_dir() . '/omega-archive-native-' . uniqid();
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
     * Builds a fresh {@see PharData} based engine backed by a new tar archive.
     *
     * @return NativePharEngine The configured engine.
     */
    private function makeEngine(): NativePharEngine
    {
        return new NativePharEngine(
            new PharData($this->tempDir . '/archive.tar')
        );
    }

    /**
     * Tests that write adds a new member which can then be read.
     *
     * @return void
     */
    public function testWriteAddsMember(): void
    {
        $engine = $this->makeEngine();

        $engine->write('hello.txt', 'hello phar payload');

        $this->assertTrue($engine->contains('hello.txt'));
        $this->assertSame('hello phar payload', $engine->read('hello.txt'));
    }

    /**
     * Tests that delete removes an existing member.
     *
     * @return void
     */
    public function testDeleteRemovesMember(): void
    {
        $engine = $this->makeEngine();
        $engine->write('hello.txt', 'hello phar payload');

        $engine->delete('hello.txt');

        $this->assertFalse($engine->contains('hello.txt'));
    }

    /**
     * Tests that write then delete results in an empty archive state.
     *
     * @return void
     */
    public function testDeleteAfterWriteLeavesArchiveEmpty(): void
    {
        $engine = $this->makeEngine();
        $engine->write('a.txt', 'alpha');
        $engine->write('b.txt', 'beta');

        $engine->delete('a.txt');

        $this->assertFalse($engine->contains('a.txt'));
        $this->assertTrue($engine->contains('b.txt'));
        $this->assertSame('beta', $engine->read('b.txt'));
    }

    /**
     * Tests that isDirectory distinguishes directories from files.
     *
     * @return void
     */
    public function testIsDirectoryDetectsDirectories(): void
    {
        $engine = $this->makeEngine();
        $engine->write('mydir/file.txt', 'nested');

        $this->assertFalse($engine->isDirectory('mydir/file.txt'));
        $this->assertTrue($engine->isDirectory('mydir'));
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
}
