<?php

declare(strict_types=1);

namespace Tests\Filesystem\Adapter\Memory;

use Omega\Filesystem\Adapter\Memory\InMemory;

covers(InMemory::class);

beforeEach(function (): void {
    $this->adapter = new InMemory([
        'file1.txt' => 'Hello World',
        'file2.txt' => [
            'content' => 'Foo Bar',
            'mtime' => 1000,
        ],
    ]);
});

it('reads an existing file', function (): void {
    expect($this->adapter->read('file1.txt'))->toBe('Hello World');
});

it('reads false for non-existent file', function (): void {
    expect($this->adapter->read('missing.txt'))->toBeFalse();
});

it('writes a file and returns byte count', function (): void {
    $bytes = $this->adapter->write('new.txt', 'test');
    expect($bytes)->toBe(4);
    expect($this->adapter->read('new.txt'))->toBe('test');
});

it('checks file existence', function (): void {
    expect($this->adapter->exists('file1.txt'))->toBeTrue();
    expect($this->adapter->exists('missing.txt'))->toBeFalse();
});

it('lists all keys', function (): void {
    $keys = $this->adapter->keys();
    expect($keys)->toBe(['file1.txt', 'file2.txt']);
});

it('returns modification time for existing file', function (): void {
    expect($this->adapter->mtime('file2.txt'))->toBe(1000);
});

it('returns false mtime for non-existent file', function (): void {
    expect($this->adapter->mtime('missing.txt'))->toBeFalse();
});

it('deletes a file', function (): void {
    expect($this->adapter->delete('file1.txt'))->toBeTrue();
    expect($this->adapter->exists('file1.txt'))->toBeFalse();
});

it('always returns false for isDirectory', function (): void {
    expect($this->adapter->isDirectory('file1.txt'))->toBeFalse();
});

it('renames an existing file', function (): void {
    expect($this->adapter->rename('file1.txt', 'renamed.txt'))->toBeTrue();
    expect($this->adapter->exists('renamed.txt'))->toBeTrue();
    expect($this->adapter->exists('file1.txt'))->toBeFalse();
});

it('returns false when renaming non-existent file', function (): void {
    expect($this->adapter->rename('missing.txt', 'new.txt'))->toBeFalse();
});

it('returns mime type for a file', function (): void {
    $mimeType = $this->adapter->mimeType('file1.txt');
    expect($mimeType)->toBeString();
});

it('constructs with no files', function (): void {
    $adapter = new InMemory();
    expect($adapter->keys())->toBe([]);
});

it('setFiles replaces all files', function (): void {
    $this->adapter->setFiles(['replaced.txt' => 'new content']);
    expect($this->adapter->keys())->toBe(['replaced.txt']);
    expect($this->adapter->read('replaced.txt'))->toBe('new content');
});

it('setFile adds a single file', function (): void {
    $this->adapter->setFile('added.txt', 'added content');
    expect($this->adapter->read('added.txt'))->toBe('added content');
    expect($this->adapter->exists('added.txt'))->toBeTrue();
});

it('setFile with null content stores empty string', function (): void {
    $this->adapter->setFile('empty.txt');
    expect($this->adapter->read('empty.txt'))->toBe('');
});
