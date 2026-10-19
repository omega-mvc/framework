<?php

declare(strict_types=1);

namespace Tests\Filesystem\Stream;

use LogicException;
use Omega\Filesystem\Stream\Local;
use Omega\Filesystem\Stream\StreamMode;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(Local::class)]
final class LocalStreamTest extends TestCase
{
    private string $tmpDir;

    private string $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/omega_stream_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
        $this->tmpFile = $this->tmpDir . '/test.txt';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->tmpDir,
                    \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo) {
                    $file->isDir()
                        ? rmdir($file->getPathname())
                        : unlink($file->getPathname());
                }
            }
            rmdir($this->tmpDir);
        }

        parent::tearDown();
    }

    public function testOpensAFileForWritingAndCreatesDirectories(): void
    {
        $dir    = $this->tmpDir . '/sub';
        $stream = new Local($dir . '/file.txt');
        $result = $stream->open(new StreamMode('w'));
        $this->assertTrue($result);
        $this->assertTrue(is_dir($dir));
        $stream->close();
    }

    public function testOpensAnExistingFileForReading(): void
    {
        file_put_contents($this->tmpFile, 'hello');
        $stream = new Local($this->tmpFile);
        $result = $stream->open(new StreamMode('r'));
        $this->assertTrue($result);
        $stream->close();
    }

    public function testThrowsWhenFopenFails(): void
    {
        $stream = new Local('/nonexistent/path/file.txt');

        $this->expectException(RuntimeException::class);

        set_error_handler(static fn (): bool => true);

        try {
            $stream->open(new StreamMode('r'));
        } finally {
            restore_error_handler();
        }
    }

    public function testReadsDataFromAnOpenStream(): void
    {
        file_put_contents($this->tmpFile, 'hello world');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('r'));
        $this->assertSame('hello', $stream->read(5));
        $stream->close();
    }

    public function testReturnsFalseWhenReadingWithoutOpen(): void
    {
        $stream = new Local($this->tmpFile);
        $this->assertFalse($stream->read(5));
    }

    public function testReturnsEmptyStringWhenCountIsLessThan1(): void
    {
        file_put_contents($this->tmpFile, 'hello');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('r'));
        $this->assertSame('', $stream->read(0));
        $this->assertSame('', $stream->read(-1));
        $stream->close();
    }

    public function testThrowsWhenReadingOnWriteOnlyMode(): void
    {
        file_put_contents($this->tmpFile, 'hello');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('w'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('The stream does not allow read.');

        $stream->read(5);
    }

    public function testWritesDataToAStream(): void
    {
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('w'));
        $bytes = $stream->write('hello');
        $this->assertSame(5, $bytes);
        $stream->close();
        $this->assertSame('hello', file_get_contents($this->tmpFile));
    }

    public function testReturnsFalseWhenWritingWithoutOpen(): void
    {
        $stream = new Local($this->tmpFile);
        $this->assertFalse($stream->write('data'));
    }

    public function testThrowsWhenWritingOnReadOnlyMode(): void
    {
        file_put_contents($this->tmpFile, 'hello');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('r'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('The stream does not allow write.');

        $stream->write('data');
    }

    public function testClosesAnOpenStream(): void
    {
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('w'));
        $stream->write('data');
        $this->assertTrue($stream->close());
    }

    public function testReturnsFalseWhenClosingWithoutOpen(): void
    {
        $stream = new Local($this->tmpFile);
        $this->assertFalse($stream->close());
    }

    public function testFlushesAnOpenStream(): void
    {
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('w'));
        $stream->write('data');
        $this->assertTrue($stream->flush());
        $stream->close();
    }

    public function testReturnsFalseWhenFlushingWithoutOpen(): void
    {
        $stream = new Local($this->tmpFile);
        $this->assertFalse($stream->flush());
    }

    public function testSeeksToAPosition(): void
    {
        file_put_contents($this->tmpFile, 'hello world');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('r+'));
        $this->assertTrue($stream->seek(6));
        $this->assertSame('world', $stream->read(5));
        $stream->close();
    }

    public function testReturnsFalseWhenSeekingWithoutOpen(): void
    {
        $stream = new Local($this->tmpFile);
        $this->assertFalse($stream->seek(0));
    }

    public function testReturnsCurrentPositionViaTell(): void
    {
        file_put_contents($this->tmpFile, 'hello');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('r'));
        $this->assertSame(0, $stream->tell());
        $stream->read(3);
        $this->assertSame(3, $stream->tell());
        $stream->close();
    }

    public function testReturnsFalseWhenTellingWithoutOpen(): void
    {
        $stream = new Local($this->tmpFile);
        $this->assertFalse($stream->tell());
    }

    public function testDetectsEndOfFile(): void
    {
        file_put_contents($this->tmpFile, 'hi');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('r'));
        $this->assertFalse($stream->eof());
        $stream->read(2);
        $stream->read(1);
        $this->assertTrue($stream->eof());
        $stream->close();
    }

    public function testReturnsTrueForEofWhenNoHandle(): void
    {
        $stream = new Local($this->tmpFile);
        $this->assertTrue($stream->eof());
    }

    public function testReturnsStatForOpenFile(): void
    {
        file_put_contents($this->tmpFile, 'hello');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('r'));
        $stat = $stream->stat();
        $this->assertIsArray($stat);
        $this->assertSame(5, $stat['size']);
        $stream->close();
    }

    public function testReturnsStatForDirectoryWhenNotOpened(): void
    {
        $stream = new Local($this->tmpDir);
        $stat   = $stream->stat();
        $this->assertIsArray($stat);
    }

    public function testReturnsFalseForStatWhenNoHandleAndNotDir(): void
    {
        $stream = new Local($this->tmpFile);
        $this->assertFalse($stream->stat());
    }

    public function testCastReturnsHandleWhenOpen(): void
    {
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('w'));
        $cast = $stream->cast(0);
        $this->assertNotFalse($cast);
        $stream->close();
    }

    public function testCastReturnsFalseWhenNotOpen(): void
    {
        $stream = new Local($this->tmpFile);
        $this->assertFalse($stream->cast(0));
    }

    public function testUnlinkDeletesFileWhenModeImpliesDeletion(): void
    {
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('w'));
        $stream->write('data');
        $this->assertTrue($stream->unlink());
        $this->assertFalse(file_exists($this->tmpFile));
    }

    public function testUnlinkReturnsFalseWhenModeDoesNotImplyDeletion(): void
    {
        file_put_contents($this->tmpFile, 'data');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('r'));
        $this->assertFalse($stream->unlink());
        $this->assertTrue(file_exists($this->tmpFile));
        $stream->close();
    }

    public function testSeeksWithSeekCur(): void
    {
        file_put_contents($this->tmpFile, 'hello');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('r'));
        $stream->read(2);
        $this->assertTrue($stream->seek(1, SEEK_CUR));
        $this->assertSame(3, $stream->tell());
        $stream->close();
    }

    public function testSeeksWithSeekEnd(): void
    {
        file_put_contents($this->tmpFile, 'hello');
        $stream = new Local($this->tmpFile);
        $stream->open(new StreamMode('r'));
        $this->assertTrue($stream->seek(-2, SEEK_END));
        $this->assertSame(3, $stream->tell());
        $stream->close();
    }
}
