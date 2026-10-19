<?php

declare(strict_types=1);

namespace Tests\Archive;

use Omega\Archive\NativePharEngine;
use PharData;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function is_dir;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(NativePharEngine::class)]
final class NativePharEngineTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/omega-archive-native-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        self::nativePharRemoveDirectory($this->tempDir);
    }

    public function testWriteAddsANewMemberWhichCanThenBeRead(): void
    {
        $engine = new NativePharEngine(
            new PharData($this->tempDir . '/archive.tar')
        );

        $engine->write('hello.txt', 'hello phar payload');

        $this->assertTrue($engine->contains('hello.txt'));
        $this->assertSame('hello phar payload', $engine->read('hello.txt'));
    }

    public function testDeleteRemovesAnExistingMember(): void
    {
        $engine = new NativePharEngine(
            new PharData($this->tempDir . '/archive.tar')
        );
        $engine->write('hello.txt', 'hello phar payload');

        $engine->delete('hello.txt');

        $this->assertFalse($engine->contains('hello.txt'));
    }

    public function testDeleteAfterWriteLeavesTheArchiveInTheExpectedState(): void
    {
        $engine = new NativePharEngine(
            new PharData($this->tempDir . '/archive.tar')
        );
        $engine->write('a.txt', 'alpha');
        $engine->write('b.txt', 'beta');

        $engine->delete('a.txt');

        $this->assertFalse($engine->contains('a.txt'));
        $this->assertTrue($engine->contains('b.txt'));
        $this->assertSame('beta', $engine->read('b.txt'));
    }

    public function testIsDirectoryDistinguishesDirectoriesFromFiles(): void
    {
        $engine = new NativePharEngine(
            new PharData($this->tempDir . '/archive.tar')
        );
        $engine->write('mydir/file.txt', 'nested');

        $this->assertFalse($engine->isDirectory('mydir/file.txt'));
        $this->assertTrue($engine->isDirectory('mydir'));
    }

    private static function nativePharRemoveDirectory(string $directory): void
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
                self::nativePharRemoveDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
