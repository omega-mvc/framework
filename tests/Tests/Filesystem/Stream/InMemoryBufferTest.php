<?php

declare(strict_types=1);

namespace Tests\Filesystem\Stream;

use LogicException;
use Omega\Filesystem\Adapter\Memory\InMemory;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\Stream\InMemoryBuffer;
use Omega\Filesystem\Stream\StreamMode;

covers(InMemoryBuffer::class);

beforeEach(function (): void {
    $this->adapter = new InMemory([
        'test.txt' => 'Hello World',
    ]);
    $this->fs = new Filesystem($this->adapter);
});

it('opens for reading existing file', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    expect($buffer->open(new StreamMode('r')))->toBeTrue();
});

it('opens for writing existing file', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    expect($buffer->open(new StreamMode('w')))->toBeTrue();
});

it('opens new file with write mode', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'new.txt');
    expect($buffer->open(new StreamMode('w')))->toBeTrue();
});

it('returns false when opening existing file with x mode', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    expect($buffer->open(new StreamMode('x')))->toBeFalse();
});

it('opens new file with x mode', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'brandnew.txt');
    expect($buffer->open(new StreamMode('x')))->toBeTrue();
});

it('reads from buffer', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    expect($buffer->read(5))->toBe('Hello');
});

it('throws when reading on write-only mode', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('w'));
    $buffer->read(5);
})->throws(LogicException::class, 'The stream does not allow read.');

it('writes to buffer', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('w'));
    $bytes = $buffer->write('New Content');
    expect($bytes)->toBe(11);
});

it('throws when writing on read-only mode', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    $buffer->write('data');
})->throws(LogicException::class, 'The stream does not allow write.');

it('close returns false and flushes', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('w'));
    $buffer->write('New Content');
    expect($buffer->close())->toBeFalse();
    expect($this->fs->has('test.txt'))->toBeTrue();
});

it('close does nothing when synchronized', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    expect($buffer->close())->toBeFalse();
});

it('seek with SEEK_SET', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    expect($buffer->seek(5))->toBeTrue();
    expect($buffer->tell())->toBe(5);
});

it('seek with SEEK_CUR', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    $buffer->read(3);
    expect($buffer->seek(2, SEEK_CUR))->toBeTrue();
    expect($buffer->tell())->toBe(5);
});

it('seek with SEEK_END', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    expect($buffer->seek(-5, SEEK_END))->toBeTrue();
    expect($buffer->tell())->toBe(6);
});

it('seek returns false for invalid whence', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    expect($buffer->seek(0, 999))->toBeFalse();
});

it('tell returns position', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    expect($buffer->tell())->toBe(0);
    $buffer->read(5);
    expect($buffer->tell())->toBe(5);
});

it('eof returns true when at end', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    expect($buffer->eof())->toBeFalse();
    $buffer->read(100);
    expect($buffer->eof())->toBeTrue();
});

it('eof returns true for empty content', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'new_empty.txt');
    $buffer->open(new StreamMode('w'));
    expect($buffer->eof())->toBeTrue();
});

it('flush returns true when synchronized', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    expect($buffer->flush())->toBeTrue();
});

it('flush returns true after writing', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('w'));
    $buffer->write('data');
    expect($buffer->flush())->toBeTrue();
});

it('stat returns stats for existing file', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    $stat = $buffer->stat();
    expect($stat)->toBeArray();
    expect($stat['size'])->toBe(11);
});

it('stat returns false for non-existent file', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'nonexistent.txt');
    expect($buffer->stat())->toBeFalse();
});

it('cast always returns false', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    expect($buffer->cast(0))->toBeFalse();
});

it('unlink deletes file when mode implies deletion', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('w'));
    expect($buffer->unlink())->toBeTrue();
    expect($this->fs->has('test.txt'))->toBeFalse();
});

it('unlink returns false when mode does not imply deletion', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r'));
    expect($buffer->unlink())->toBeFalse();
});

it('write appends at end of content', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('w'));
    $buffer->write('Hello');
    $buffer->write(' World');
    $buffer->close();
    expect($this->adapter->read('test.txt'))->toBe('Hello World');
});

it('write replaces at non-eof position', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('r+'));
    $buffer->seek(6);
    $buffer->write('Beautiful ');
    $buffer->close();
    expect($this->adapter->read('test.txt'))->toBe(
        'Hello Beautiful '
    );
});

it('write pads when hasNewContentAtFurtherPosition', function (): void {
    $this->adapter->write('pad.txt', 'content');
    $buffer = new InMemoryBuffer($this->fs, 'pad.txt');
    $buffer->open(new StreamMode('r+'));
    $buffer->seek(20);
    $buffer->write('X');
    $buffer->close();
    $read = $this->adapter->read('pad.txt');
    expect($read)->toContain('X');
});

it('open with append mode positions cursor at end', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('a'));
    expect($buffer->tell())->toBe(11);
    $buffer->write('!');
    $buffer->close();
    expect($this->adapter->read('test.txt'))->toBe('Hello World!');
});

it('write with w mode deletes existing content', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'test.txt');
    $buffer->open(new StreamMode('w'));
    $buffer->write('New');
    $buffer->close();
    expect($this->adapter->read('test.txt'))->toBe('New');
});

it('open new file with a mode allows writing', function (): void {
    $buffer = new InMemoryBuffer($this->fs, 'appended.txt');
    $buffer->open(new StreamMode('a'));
    $buffer->write('first');
    $buffer->close();
    expect($this->adapter->read('appended.txt'))->toBe('first');
});
