<?php

declare(strict_types=1);

namespace Tests\Filesystem;

use Omega\Filesystem\Adapter\Memory\InMemory;
use Omega\Filesystem\Exception\FileNotFoundException;
use Omega\Filesystem\File;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\Stream\StreamInterface;

covers(File::class);

beforeEach(function (): void {
    $this->adapter = new InMemory([
        'test.txt' => 'Hello World',
    ]);
    $this->fs = new Filesystem($this->adapter);
    $this->file = $this->fs->get('test.txt');
});

it('returns the key', function (): void {
    expect($this->file->getKey())->toBe('test.txt');
});

it('returns the name', function (): void {
    expect($this->file->getName())->toBe('test.txt');
});

it('sets and gets the name', function (): void {
    $this->file->setName('renamed.txt');
    expect($this->file->getName())->toBe('renamed.txt');
});

it('gets content lazily from filesystem', function (): void {
    expect($this->file->getContent())->toBe('Hello World');
});

it('caches content after first read', function (): void {
    $content1 = $this->file->getContent();
    $this->adapter->write('test.txt', 'Changed');
    $content2 = $this->file->getContent();
    expect($content2)->toBe($content1);
});

it('gets size from filesystem', function (): void {
    expect($this->file->getSize())->toBe(11);
});

it('sets size directly', function (): void {
    $this->file->setSize(100);
    expect($this->file->getSize())->toBe(100);
});

it('gets modification time', function (): void {
    $mtime = $this->file->getMtime();
    expect($mtime)->toBeInt();
});

it('sets content and returns byte count', function (): void {
    $bytes = $this->file->setContent('New content');
    expect($bytes)->toBe(11);
    expect($this->file->getContent())->toBe('New content');
});

it('checks existence', function (): void {
    expect($this->file->exists())->toBeTrue();
});

it('deletes file', function (): void {
    expect($this->file->delete())->toBeTrue();
    expect($this->file->exists())->toBeFalse();
});

it('creates a stream', function (): void {
    $stream = $this->file->createStream();
    expect($stream)->toBeInstanceOf(StreamInterface::class);
});

it('renames file', function (): void {
    $this->file->rename('new-key.txt');
    expect($this->file->getKey())->toBe('new-key.txt');
    expect($this->fs->has('new-key.txt'))->toBeTrue();
});

it('returns 0 size for non-existent file', function (): void {
    $file = $this->fs->get('missing.txt', true);
    expect($file->getSize())->toBe(0);
});
