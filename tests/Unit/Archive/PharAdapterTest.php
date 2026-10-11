<?php

declare(strict_types=1);

namespace Tests\Archive;

use Omega\Archive\NativePharEngine;
use Omega\Archive\PharAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

use function is_dir;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function scandir;
use function str_contains;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(PharAdapter::class)]
#[CoversClass(NativePharEngine::class)]
final class PharAdapterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/omega-archive-phar-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        self::pharAdapterRemoveDirectory($this->tempDir);
    }

    public function testAcceptsAnExistingPharArchiveInTheConstructor(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->assertSame('hello phar payload', $adapter->read('hello.txt'));
    }

    public function testThrowsWhenTheFileDoesNotExistInTheConstructor(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        new PharAdapter($this->tempDir . '/missing.phar');
    }

    public function testAcceptsAnExistingFileOnOpen(): void
    {
        $sample  = __DIR__ . '/fixtures/archive/sample.phar';
        $adapter = new PharAdapter($sample);

        $adapter->open($sample);

        $this->assertSame('hello phar payload', $adapter->read('hello.txt'));
    }

    public function testThrowsWhenTheFileDoesNotExistOnOpen(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $adapter->open($this->tempDir . '/missing.phar');
    }

    public function testThrowsWhenThePharArchiveIsCorruptOnOpen(): void
    {
        $file = $this->tempDir . '/corrupt.phar';
        file_put_contents($file, 'this is plain text, not a phar archive');

        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to open Phar archive');

        $adapter->open($file);
    }

    public function testCloseIsCallableWithoutThrowing(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $adapter->close();

        $this->assertTrue($adapter->exists('hello.txt'));
    }

    public function testReturnsTheStoredContentOfAMemberOnRead(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->assertSame('hello phar payload', $adapter->read('hello.txt'));
    }

    public function testThrowsWhenTheMemberDoesNotExistOnRead(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist in the Phar archive');

        $adapter->read('nope.txt');
    }

    public function testExistsReflectsMembershipInTheArchive(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->assertTrue($adapter->exists('hello.txt'));
        $this->assertFalse($adapter->exists('missing.txt'));
    }

    public function testReturnsTheStreamUrisOfTheArchiveMembersOnKeys(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $keys = $adapter->keys();

        $this->assertCount(1, $keys);
        $this->assertStringStartsWith('phar://', $keys[0]);
        $this->assertStringContainsString('hello.txt', $keys[0]);
    }

    public function testIsDirectoryDistinguishesDirectoriesFromFiles(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->assertFalse($adapter->isDirectory('hello.txt'));
    }

    public function testThrowsWhenTheKeyDoesNotExistOnIsDirectory(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $adapter->isDirectory('nope.txt');
    }

    public function testReturnsAnIntegerTimestampOnMtime(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->assertIsInt($adapter->mtime('hello.txt'));
    }

    public function testThrowsWhenTheMemberDoesNotExistOnMtime(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $adapter->mtime('nope.txt');
    }

    public function testWriteAddsAMemberAndReturnsItsLength(): void
    {
        $fake    = new FakePharEngine();
        $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

        $length = $adapter->write('new.txt', 'abcde');

        $this->assertSame(5, $length);
        $this->assertSame('abcde', $adapter->read('new.txt'));
        $this->assertSame(['new.txt' => 'abcde'], $fake->state());
    }

    public function testDeleteRemovesAMember(): void
    {
        $fake    = new FakePharEngine(['hello.txt' => 'hello phar payload']);
        $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

        $this->assertTrue($adapter->delete('hello.txt'));
        $this->assertFalse($adapter->exists('hello.txt'));
    }

    public function testThrowsWhenTheKeyDoesNotExistOnDelete(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist and cannot be deleted');

        $adapter->delete('nope.txt');
    }

    public function testRenameMovesAMemberAndRemovesTheSource(): void
    {
        $fake    = new FakePharEngine(['hello.txt' => 'hello phar payload']);
        $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

        $this->assertTrue($adapter->rename('hello.txt', 'renamed.txt'));
        $this->assertFalse($adapter->exists('hello.txt'));
        $this->assertTrue($adapter->exists('renamed.txt'));
        $this->assertSame('hello phar payload', $adapter->read('renamed.txt'));
        $this->assertSame(['renamed.txt' => 'hello phar payload'], $fake->state());
    }

    public function testThrowsWhenTheSourceMemberDoesNotExistOnRename(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $adapter->rename('missing.txt', 'renamed.txt');
    }

    public function testThrowsWhenTheTargetMemberAlreadyExistsOnRename(): void
    {
        $adapter = new PharAdapter(__DIR__ . '/fixtures/archive/sample.phar');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        $adapter->rename('hello.txt', 'hello.txt');
    }

    public function testThrowsWhenTheSourceIsADirectoryMemberOnRename(): void
    {
        $fake    = new FailingPharEngine(['mydir' => '']);
        $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to rename');

        $adapter->rename('mydir', 'renamed');
    }

    public function testThrowsWhenTheContentIsUnreadableOnRename(): void
    {
        $fake    = new UnreadablePharEngine(['hello.txt' => 'hello phar payload']);
        $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to rename');

        $adapter->rename('hello.txt', 'renamed.txt');
    }

    private static function pharAdapterRemoveDirectory(string $directory): void
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
                self::pharAdapterRemoveDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
