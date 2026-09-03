<?php

declare(strict_types=1);

namespace Tests\Filesystem;

use InvalidArgumentException;
use LogicException;
use Omega\Filesystem\Exception\FileAlreadyExistsException;
use Omega\Filesystem\Exception\FileNotFoundException;
use Omega\Filesystem\Exception\UnexpectedFileExcption;
use Omega\Filesystem\Adapter\Memory\InMemory;
use Omega\Filesystem\File;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\Stream\InMemoryBuffer;
use Omega\Filesystem\Stream\StreamInterface;
use RuntimeException;

covers(Filesystem::class);

beforeEach(function (): void {
    $this->adapter = new InMemory();
    $this->fs = new Filesystem($this->adapter);
});

it('returns the adapter', function (): void {
    expect($this->fs->getAdapter())->toBe($this->adapter);
});

it('checks if key exists', function (): void {
    $this->adapter->write('file.txt', 'content');
    expect($this->fs->has('file.txt'))->toBeTrue();
    expect($this->fs->has('missing.txt'))->toBeFalse();
});

it('throws on empty key', function (): void {
    $this->fs->has('');
})->throws(InvalidArgumentException::class, 'Object path is empty.');

it('writes a new file and returns byte count', function (): void {
    $bytes = $this->fs->write('new.txt', 'hello');
    expect($bytes)->toBe(5);
    expect($this->fs->read('new.txt'))->toBe('hello');
});

it('throws when writing existing file without overwrite', function (): void {
    $this->fs->write('file.txt', 'content');
    $this->fs->write('file.txt', 'new content');
})->throws(FileAlreadyExistsException::class);

it('overwrites existing file when overwrite is true', function (): void {
    $this->fs->write('file.txt', 'old');
    $this->fs->write('file.txt', 'new', true);
    expect($this->fs->read('file.txt'))->toBe('new');
});

it('reads an existing file', function (): void {
    $this->fs->write('file.txt', 'content');
    expect($this->fs->read('file.txt'))->toBe('content');
});

it('throws when reading non-existent file', function (): void {
    $this->fs->read('missing.txt');
})->throws(FileNotFoundException::class);

it('deletes an existing file', function (): void {
    $this->fs->write('file.txt', 'content');
    expect($this->fs->delete('file.txt'))->toBeTrue();
    expect($this->fs->has('file.txt'))->toBeFalse();
});

it('throws when deleting non-existent file', function (): void {
    $this->fs->delete('missing.txt');
})->throws(FileNotFoundException::class);

it('gets a file object', function (): void {
    $this->fs->write('file.txt', 'content');
    $file = $this->fs->get('file.txt');
    expect($file)->toBeInstanceOf(File::class);
    expect($file->getKey())->toBe('file.txt');
});

it('gets a file with create flag', function (): void {
    $file = $this->fs->get('new.txt', true);
    expect($file)->toBeInstanceOf(File::class);
});

it('throws when getting non-existent file without create', function (): void {
    $this->fs->get('missing.txt');
})->throws(FileNotFoundException::class);

it('gets a file from register', function (): void {
    $file1 = $this->fs->get('file.txt', true);
    $file2 = $this->fs->get('file.txt', true);
    expect($file1)->toBe($file2);
});

it('returns keys', function (): void {
    $this->fs->write('a.txt', 'a');
    $this->fs->write('b.txt', 'b');
    $keys = $this->fs->keys();
    expect($keys)->toHaveCount(2);
});

it('lists keys with prefix', function (): void {
    $this->fs->write('dir/file.txt', 'content');
    $this->fs->write('other.txt', 'other');
    $result = $this->fs->listKeys('dir/');
    expect($result['keys'])->toContain('dir/file.txt');
});

it('returns modification time', function (): void {
    $this->fs->write('file.txt', 'content');
    $mtime = $this->fs->mtime('file.txt');
    expect($mtime)->toBeInt();
});

it('throws when getting mtime of non-existent file', function (): void {
    $this->fs->mtime('missing.txt');
})->throws(FileNotFoundException::class);

it('computes checksum', function (): void {
    $this->fs->write('file.txt', 'hello');
    $checksum = $this->fs->checksum('file.txt');
    expect($checksum)->toBe(md5('hello'));
});

it('computes size', function (): void {
    $this->fs->write('file.txt', 'hello');
    expect($this->fs->size('file.txt'))->toBe(5);
});

it('creates a stream for non-stream adapter', function (): void {
    $this->fs->write('file.txt', 'content');
    $stream = $this->fs->createStream('file.txt');
    expect($stream)->toBeInstanceOf(InMemoryBuffer::class);
});

it('creates a file object', function (): void {
    $file = $this->fs->createFile('file.txt');
    expect($file)->toBeInstanceOf(File::class);
});

it('returns mime type for file with mime provider', function (): void {
    $this->fs->write('file.txt', 'hello');
    $mimeType = $this->fs->mimeType('file.txt');
    expect($mimeType)->toBeString();
});

it('throws when getting mime type of non-existent file', function (): void {
    $this->fs->mimeType('missing.txt');
})->throws(FileNotFoundException::class);

it('checks if key is a directory', function (): void {
    expect($this->fs->isDirectory('anything'))->toBeFalse();
});

it('clears file register', function (): void {
    $file1 = $this->fs->get('file.txt', true);
    $this->fs->clearFileRegister();
    $file2 = $this->fs->createFile('file.txt');
    expect($file1)->not->toBe($file2);
});

it('removes from register', function (): void {
    $file1 = $this->fs->get('file.txt', true);
    $this->fs->removeFromRegister('file.txt');
    $file2 = $this->fs->createFile('file.txt');
    expect($file1)->not->toBe($file2);
});

it('rename moves a file', function (): void {
    $this->fs->write('old.txt', 'content');
    expect($this->fs->rename('old.txt', 'new.txt'))->toBeTrue();
    expect($this->fs->has('new.txt'))->toBeTrue();
    expect($this->fs->has('old.txt'))->toBeFalse();
});

it('rename throws when target exists', function (): void {
    $this->fs->write('old.txt', 'content');
    $this->fs->write('new.txt', 'other');
    $this->fs->rename('old.txt', 'new.txt');
})->throws(UnexpectedFileExcption::class);

it('rename throws when source not found', function (): void {
    $this->fs->rename('missing.txt', 'new.txt');
})->throws(FileNotFoundException::class);
