<?php

declare(strict_types=1);

namespace Tests\Filesystem\Stream;

use LogicException;
use Omega\Filesystem\Adapter\Memory\InMemory;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\Stream\InMemoryBuffer;
use Omega\Filesystem\Stream\StreamMode;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(InMemoryBuffer::class)]
final class InMemoryBufferTest extends TestCase
{
    private InMemory $adapter;

    private Filesystem $fs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = new InMemory([
            'test.txt' => 'Hello World',
        ]);
        $this->fs = new Filesystem($this->adapter);
    }

    public function testOpensForReadingExistingFile(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $this->assertTrue($buffer->open(new StreamMode('r')));
    }

    public function testOpensForWritingExistingFile(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $this->assertTrue($buffer->open(new StreamMode('w')));
    }

    public function testOpensNewFileWithWriteMode(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'new.txt');
        $this->assertTrue($buffer->open(new StreamMode('w')));
    }

    public function testReturnsFalseWhenOpeningExistingFileWithXMode(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $this->assertFalse($buffer->open(new StreamMode('x')));
    }

    public function testOpensNewFileWithXMode(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'brandnew.txt');
        $this->assertTrue($buffer->open(new StreamMode('x')));
    }

    public function testReadsFromBuffer(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $this->assertSame('Hello', $buffer->read(5));
    }

    public function testThrowsWhenReadingOnWriteOnlyMode(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('w'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('The stream does not allow read.');

        $buffer->read(5);
    }

    public function testWritesToBuffer(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('w'));
        $bytes = $buffer->write('New Content');
        $this->assertSame(11, $bytes);
    }

    public function testThrowsWhenWritingOnReadOnlyMode(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('The stream does not allow write.');

        $buffer->write('data');
    }

    public function testCloseReturnsFalseAndFlushes(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('w'));
        $buffer->write('New Content');
        $buffer->close();
        $this->assertSame('New Content', $this->adapter->read('test.txt'));
        $this->assertTrue($this->fs->has('test.txt'));
    }

    public function testCloseDoesNothingWhenSynchronized(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $buffer->close();
        $this->assertSame('Hello World', $this->adapter->read('test.txt'));
    }

    public function testSeekWithSeekSet(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $this->assertTrue($buffer->seek(5));
        $this->assertSame(5, $buffer->tell());
    }

    public function testSeekWithSeekCur(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $buffer->read(3);
        $this->assertTrue($buffer->seek(2, SEEK_CUR));
        $this->assertSame(5, $buffer->tell());
    }

    public function testSeekWithSeekEnd(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $this->assertTrue($buffer->seek(-5, SEEK_END));
        $this->assertSame(6, $buffer->tell());
    }

    public function testSeekReturnsFalseForInvalidWhence(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $this->assertFalse($buffer->seek(0, 999));
    }

    public function testTellReturnsPosition(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $this->assertSame(0, $buffer->tell());
        $buffer->read(5);
        $this->assertSame(5, $buffer->tell());
    }

    public function testEofReturnsTrueWhenAtEnd(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $this->assertFalse($buffer->eof());
        $buffer->read(100);
        $this->assertTrue($buffer->eof());
    }

    public function testEofReturnsTrueForEmptyContent(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'new_empty.txt');
        $buffer->open(new StreamMode('w'));
        $this->assertTrue($buffer->eof());
    }

    public function testFlushReturnsTrueWhenSynchronized(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $this->assertTrue($buffer->flush());
    }

    public function testFlushReturnsTrueAfterWriting(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('w'));
        $buffer->write('data');
        $this->assertTrue($buffer->flush());
    }

    public function testStatReturnsStatsForExistingFile(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $stat = $buffer->stat();
        $this->assertIsArray($stat);
        $this->assertSame(11, $stat['size']);
    }

    public function testStatReturnsFalseForNonExistentFile(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'nonexistent.txt');
        $this->assertFalse($buffer->stat());
    }

    public function testCastAlwaysReturnsFalse(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $this->assertSame(false, $buffer->cast(0));
    }

    public function testUnlinkDeletesFileWhenModeImpliesDeletion(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('w'));
        $this->assertTrue($buffer->unlink());
        $this->assertFalse($this->fs->has('test.txt'));
    }

    public function testUnlinkReturnsFalseWhenModeDoesNotImplyDeletion(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r'));
        $this->assertFalse($buffer->unlink());
    }

    public function testWriteAppendsAtEndOfContent(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('w'));
        $buffer->write('Hello');
        $buffer->write(' World');
        $buffer->close();
        $this->assertSame('Hello World', $this->adapter->read('test.txt'));
    }

    public function testWriteReplacesAtNonEofPosition(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('r+'));
        $buffer->seek(6);
        $buffer->write('Beautiful ');
        $buffer->close();
        $this->assertSame('Hello Beautiful ', $this->adapter->read('test.txt'));
    }

    public function testWritePadsWhenHasNewContentAtFurtherPosition(): void
    {
        $this->adapter->write('pad.txt', 'content');
        $buffer = new InMemoryBuffer($this->fs, 'pad.txt');
        $buffer->open(new StreamMode('r+'));
        $buffer->seek(20);
        $buffer->write('X');
        $buffer->close();
        $read = $this->adapter->read('pad.txt');
        $this->assertIsString($read);
        $this->assertStringContainsString('X', $read);
    }

    public function testOpenWithAppendModePositionsCursorAtEnd(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('a'));
        $this->assertSame(11, $buffer->tell());
        $buffer->write('!');
        $buffer->close();
        $this->assertSame('Hello World!', $this->adapter->read('test.txt'));
    }

    public function testWriteWithWModeDeletesExistingContent(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'test.txt');
        $buffer->open(new StreamMode('w'));
        $buffer->write('New');
        $buffer->close();
        $this->assertSame('New', $this->adapter->read('test.txt'));
    }

    public function testOpenNewFileWithAModeAllowsWriting(): void
    {
        $buffer = new InMemoryBuffer($this->fs, 'appended.txt');
        $buffer->open(new StreamMode('a'));
        $buffer->write('first');
        $buffer->close();
        $this->assertSame('first', $this->adapter->read('appended.txt'));
    }
}
