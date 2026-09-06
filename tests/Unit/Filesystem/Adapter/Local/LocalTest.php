<?php

declare(strict_types=1);

namespace Tests\Filesystem\Adapter\Local;

use InvalidArgumentException;
use OutOfBoundsException;
use RuntimeException;
use Omega\Filesystem\Adapter\Local\Local;
use Omega\Filesystem\Util\Checksum;
use Omega\Filesystem\Util\Size;

covers(Local::class);

beforeEach(function (): void {
    $this->dir = sys_get_temp_dir() . '/omega_test_' . uniqid();
    mkdir($this->dir, 0777, true);
});

afterEach(function (): void {
    if (is_dir($this->dir)) {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->dir);
    }
});

it('constructs with existing directory', function (): void {
    $adapter = new Local($this->dir);
    expect($adapter)->toBeInstanceOf(Local::class);
});

it('throws when directory does not exist and create is false', function (): void {
    $adapter = new Local($this->dir);
    rmdir($this->dir);
    $adapter->read('file.txt');
})->throws(RuntimeException::class);

it('creates directory lazily when create is true', function (): void {
    $newDir = $this->dir . '/newdir';
    $adapter = new Local($newDir, true);
    $adapter->write('test.txt', 'content');
    expect(is_dir($newDir))->toBeTrue();
});

it('writes and reads a file', function (): void {
    $adapter = new Local($this->dir, true);
    $bytes = $adapter->write('test.txt', 'Hello');
    expect($bytes)->toBe(5);
    expect($adapter->read('test.txt'))->toBe('Hello');
});

it('returns false when reading directory', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('file.txt', 'content');
    expect($adapter->read('.'))->toBeFalse();
});

it('checks file existence', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('test.txt', 'content');
    expect($adapter->exists('test.txt'))->toBeTrue();
    expect($adapter->exists('missing.txt'))->toBeFalse();
});

it('lists keys', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('b.txt', 'b');
    $adapter->write('a.txt', 'a');
    $keys = $adapter->keys();
    expect($keys)->toBe(['a.txt', 'b.txt']);
});

it('returns modification time', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('test.txt', 'content');
    $mtime = $adapter->mtime('test.txt');
    expect($mtime)->toBeInt();
});

it('deletes a file', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('test.txt', 'content');
    expect($adapter->delete('test.txt'))->toBeTrue();
    expect($adapter->exists('test.txt'))->toBeFalse();
});

it('returns false when deleting non-existent file', function (): void {
    $adapter = new Local($this->dir, true);
    expect($adapter->delete('missing.txt'))->toBeFalse();
});

it('renames a file', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('old.txt', 'content');
    expect($adapter->rename('old.txt', 'new.txt'))->toBeTrue();
    expect($adapter->exists('new.txt'))->toBeTrue();
    expect($adapter->exists('old.txt'))->toBeFalse();
});

it('checks if path is a directory', function (): void {
    $adapter = new Local($this->dir, true);
    expect($adapter->isDirectory('.'))->toBeTrue();
    $adapter->write('file.txt', 'content');
    expect($adapter->isDirectory('file.txt'))->toBeFalse();
});

it('computes checksum', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('test.txt', 'hello');
    expect($adapter->checksum('test.txt'))->toBe(Checksum::fromContent('hello'));
});

it('computes size', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('test.txt', 'hello');
    expect($adapter->size('test.txt'))->toBe(5);
});

it('returns mime type', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('test.txt', 'hello');
    $mimeType = $adapter->mimeType('test.txt');
    expect($mimeType)->toBeString();
});

it('computes key from path', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('subdir/file.txt', 'content');
    $key = $adapter->computeKey($this->dir . '/subdir/file.txt');
    expect($key)->toBe('subdir/file.txt');
});

it('deletes a directory recursively', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('subdir/file.txt', 'content');
    expect($adapter->delete('subdir'))->toBeTrue();
    expect($adapter->isDirectory('subdir'))->toBeFalse();
});

it('throws when deleting root directory', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->delete('.');
})->throws(InvalidArgumentException::class);

it('creates a stream', function (): void {
    $adapter = new Local($this->dir, true);
    $adapter->write('test.txt', 'content');
    $stream = $adapter->createStream('test.txt');
    expect($stream)->toBeInstanceOf(\Omega\Filesystem\Stream\Local::class);
});
