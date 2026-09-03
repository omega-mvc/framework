<?php

declare(strict_types=1);

namespace Tests\Filesystem\Stream;

use LogicException;
use RuntimeException;
use Omega\Filesystem\Stream\Local;
use Omega\Filesystem\Stream\StreamMode;

covers(Local::class);

beforeEach(function (): void {
    $this->tmpDir = sys_get_temp_dir() . '/omega_stream_test_' . uniqid();
    mkdir($this->tmpDir, 0777, true);
    $this->tmpFile = $this->tmpDir . '/test.txt';
});

afterEach(function (): void {
    if (is_dir($this->tmpDir)) {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->tmpDir,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir()
                ? rmdir($file->getPathname())
                : unlink($file->getPathname());
        }
        rmdir($this->tmpDir);
    }
});

it('opens a file for writing and creates directories', function (): void {
    $dir = $this->tmpDir . '/sub';
    $stream = new Local($dir . '/file.txt');
    $result = $stream->open(new StreamMode('w'));
    expect($result)->toBeTrue();
    expect(is_dir($dir))->toBeTrue();
    $stream->close();
});

it('opens an existing file for reading', function (): void {
    file_put_contents($this->tmpFile, 'hello');
    $stream = new Local($this->tmpFile);
    $result = $stream->open(new StreamMode('r'));
    expect($result)->toBeTrue();
    $stream->close();
});

it('throws when fopen fails', function (): void {
    $stream = new Local('/nonexistent/path/file.txt');
    $stream->open(new StreamMode('r'));
})->throws(RuntimeException::class);

it('reads data from an open stream', function (): void {
    file_put_contents($this->tmpFile, 'hello world');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('r'));
    expect($stream->read(5))->toBe('hello');
    $stream->close();
});

it('returns false when reading without open', function (): void {
    $stream = new Local($this->tmpFile);
    expect($stream->read(5))->toBeFalse();
});

it('returns empty string when count is less than 1', function (): void {
    file_put_contents($this->tmpFile, 'hello');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('r'));
    expect($stream->read(0))->toBe('');
    expect($stream->read(-1))->toBe('');
    $stream->close();
});

it('throws when reading on write-only mode', function (): void {
    file_put_contents($this->tmpFile, 'hello');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('w'));
    $stream->read(5);
})->throws(LogicException::class, 'The stream does not allow read.');

it('writes data to a stream', function (): void {
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('w'));
    $bytes = $stream->write('hello');
    expect($bytes)->toBe(5);
    $stream->close();
    expect(file_get_contents($this->tmpFile))->toBe('hello');
});

it('returns false when writing without open', function (): void {
    $stream = new Local($this->tmpFile);
    expect($stream->write('data'))->toBeFalse();
});

it('throws when writing on read-only mode', function (): void {
    file_put_contents($this->tmpFile, 'hello');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('r'));
    $stream->write('data');
})->throws(LogicException::class, 'The stream does not allow write.');

it('closes an open stream', function (): void {
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('w'));
    $stream->write('data');
    expect($stream->close())->toBeTrue();
});

it('returns false when closing without open', function (): void {
    $stream = new Local($this->tmpFile);
    expect($stream->close())->toBeFalse();
});

it('flushes an open stream', function (): void {
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('w'));
    $stream->write('data');
    expect($stream->flush())->toBeTrue();
    $stream->close();
});

it('returns false when flushing without open', function (): void {
    $stream = new Local($this->tmpFile);
    expect($stream->flush())->toBeFalse();
});

it('seeks to a position', function (): void {
    file_put_contents($this->tmpFile, 'hello world');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('r+'));
    expect($stream->seek(6))->toBeTrue();
    expect($stream->read(5))->toBe('world');
    $stream->close();
});

it('returns false when seeking without open', function (): void {
    $stream = new Local($this->tmpFile);
    expect($stream->seek(0))->toBeFalse();
});

it('returns current position via tell', function (): void {
    file_put_contents($this->tmpFile, 'hello');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('r'));
    expect($stream->tell())->toBe(0);
    $stream->read(3);
    expect($stream->tell())->toBe(3);
    $stream->close();
});

it('returns false when telling without open', function (): void {
    $stream = new Local($this->tmpFile);
    expect($stream->tell())->toBeFalse();
});

it('detects end of file', function (): void {
    file_put_contents($this->tmpFile, 'hi');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('r'));
    expect($stream->eof())->toBeFalse();
    $stream->read(2);
    $stream->read(1);
    expect($stream->eof())->toBeTrue();
    $stream->close();
});

it('returns true for eof when no handle', function (): void {
    $stream = new Local($this->tmpFile);
    expect($stream->eof())->toBeTrue();
});

it('returns stat for open file', function (): void {
    file_put_contents($this->tmpFile, 'hello');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('r'));
    $stat = $stream->stat();
    expect($stat)->toBeArray();
    expect($stat['size'])->toBe(5);
    $stream->close();
});

it('returns stat for directory when not opened', function (): void {
    $stream = new Local($this->tmpDir);
    $stat = $stream->stat();
    expect($stat)->toBeArray();
});

it('returns false for stat when no handle and not dir', function (): void {
    $stream = new Local($this->tmpFile);
    expect($stream->stat())->toBeFalse();
});

it('cast returns handle when open', function (): void {
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('w'));
    $cast = $stream->cast(0);
    expect($cast)->not->toBeFalse();
    $stream->close();
});

it('cast returns false when not open', function (): void {
    $stream = new Local($this->tmpFile);
    expect($stream->cast(0))->toBeFalse();
});

it('unlink deletes file when mode implies deletion', function (): void {
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('w'));
    $stream->write('data');
    expect($stream->unlink())->toBeTrue();
    expect(file_exists($this->tmpFile))->toBeFalse();
});

it('unlink returns false when mode does not imply deletion', function (): void {
    file_put_contents($this->tmpFile, 'data');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('r'));
    expect($stream->unlink())->toBeFalse();
    expect(file_exists($this->tmpFile))->toBeTrue();
    $stream->close();
});

it('seeks with SEEK_CUR', function (): void {
    file_put_contents($this->tmpFile, 'hello');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('r'));
    $stream->read(2);
    expect($stream->seek(1, SEEK_CUR))->toBeTrue();
    expect($stream->tell())->toBe(3);
    $stream->close();
});

it('seeks with SEEK_END', function (): void {
    file_put_contents($this->tmpFile, 'hello');
    $stream = new Local($this->tmpFile);
    $stream->open(new StreamMode('r'));
    expect($stream->seek(-2, SEEK_END))->toBeTrue();
    expect($stream->tell())->toBe(3);
    $stream->close();
});
