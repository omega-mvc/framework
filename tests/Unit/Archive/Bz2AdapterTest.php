<?php

/**
 * Part of Omega - Tests\Archive Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Archive;

use Omega\Archive\Bz2Adapter;
use RuntimeException;
use Tests\FixturesPathTrait;

use function bzcompress;
use function chmod;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function is_readable;
use function is_string;
use function is_writable;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function restore_error_handler;
use function scandir;
use function set_error_handler;
use function str_contains;
use function strlen;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

covers(Bz2Adapter::class);

uses(FixturesPathTrait::class);

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir() . '/omega-archive-bz2-' . uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    bz2RemoveDirectory($this->tempDir);
});

it('stores the bz2 file path in the constructor', function (): void {
    $adapter = new Bz2Adapter($this->tempDir . '/data.bz2');

    expect(readProperty($adapter))->toBe($this->tempDir . '/data.bz2');
});

it('throws when the file does not exist on open', function (): void {
    $adapter = new Bz2Adapter($this->tempDir . '/missing.bz2');

    $adapter->open($this->tempDir . '/nope.bz2');
})->throws(RuntimeException::class, 'does not exist');

it('throws when the file is not readable on open', function (): void {
    $file = $this->tempDir . '/unreadable.bz2';
    file_put_contents($file, 'data');
    chmod($file, 0000);

    if (is_readable($file)) {
        $this->markTestSkipped('File permissions do not allow simulating an unreadable file.');
    }

    $adapter = new Bz2Adapter($file);

    $adapter->open($file);

    chmod($file, 0644);
})->throws(RuntimeException::class, 'is not readable');

it('accepts an existing readable file on open', function (): void {
    $adapter = new Bz2Adapter($this->tempDir . '/other.bz2');

    $adapter->open($this->setFixturePath('/fixtures/archive/sample.bz2'));

    expect(readProperty($adapter))->toBe($this->setFixturePath('/fixtures/archive/sample.bz2'));
});

it('close is a no-op for the bz2 adapter', function (): void {
    $adapter = new Bz2Adapter($this->setFixturePath('/fixtures/archive/sample.bz2'));

    $adapter->close();

    expect(file_exists($this->setFixturePath('/fixtures/archive/sample.bz2')))->toBeTrue();
});

it('returns the decompressed content on read', function (): void {
    $adapter = new Bz2Adapter($this->setFixturePath('/fixtures/archive/sample.bz2'));

    expect($adapter->read(''))->toBe('hello bz2 payload');
});

it('returns false for corrupted content on read', function (): void {
    $file = $this->tempDir . '/corrupt.bz2';
    file_put_contents($file, 'this is not valid bzip2 data ----');

    $adapter = new Bz2Adapter($file);

    expect($adapter->read(''))->toBeFalse();
});

it('compresses the content and returns the compressed length on write', function (): void {
    $file = $this->tempDir . '/written.bz2';
    $adapter = new Bz2Adapter($file);

    $length = $adapter->write('', 'hello write payload');

    $compressed = bzcompress('hello write payload');

    if (!is_string($compressed)) {
        $this->fail('Expected bzcompress to return a compressed string.');
    }

    expect($length)->toBe(strlen($compressed));
    expect($adapter->read(''))->toBe('hello write payload');
});

it('delete is not supported and returns false', function (): void {
    $adapter = new Bz2Adapter($this->setFixturePath('/fixtures/archive/sample.bz2'));

    expect($adapter->delete(''))->toBeFalse();
});

it('exists reflects whether the bz2 file exists on disk', function (): void {
    $adapter = new Bz2Adapter($this->setFixturePath('/fixtures/archive/sample.bz2'));

    expect($adapter->exists(''))->toBeTrue();
    expect((new Bz2Adapter($this->tempDir . '/missing.bz2'))->exists(''))->toBeFalse();
});

it('keys is not supported and returns an empty array', function (): void {
    $adapter = new Bz2Adapter($this->setFixturePath('/fixtures/archive/sample.bz2'));

    expect($adapter->keys())->toBe([]);
});

it('isDirectory is not supported and returns false', function (): void {
    $adapter = new Bz2Adapter($this->setFixturePath('/fixtures/archive/sample.bz2'));

    expect($adapter->isDirectory(''))->toBeFalse();
});

it('returns an integer timestamp on mtime', function (): void {
    $adapter = new Bz2Adapter($this->setFixturePath('/fixtures/archive/sample.bz2'));

    expect($adapter->mtime(''))->toBeInt();
});

it('throws when the file does not exist on mtime', function (): void {
    $adapter = new Bz2Adapter($this->tempDir . '/missing.bz2');

    set_error_handler(static function (int $severity, string $message): bool {
        return str_contains($message, 'filemtime');
    });

    try {
        $adapter->mtime('');
    } finally {
        restore_error_handler();
    }
})->throws(RuntimeException::class, 'modification time');

it('rename moves a file successfully', function (): void {
    $source = $this->tempDir . '/source.bz2';
    $target = $this->tempDir . '/target.bz2';
    file_put_contents($source, 'payload');

    $adapter = new Bz2Adapter($source);

    expect($adapter->rename($source, $target))->toBeTrue();
    expect(file_exists($source))->toBeFalse();
    expect(file_exists($target))->toBeTrue();
});

it('throws when the source file does not exist on rename', function (): void {
    $adapter = new Bz2Adapter($this->tempDir . '/x.bz2');

    $adapter->rename($this->tempDir . '/missing.bz2', $this->tempDir . '/target.bz2');
})->throws(RuntimeException::class, 'Source file');

it('throws when the target file already exists on rename', function (): void {
    $source = $this->tempDir . '/source.bz2';
    $target = $this->tempDir . '/target.bz2';
    file_put_contents($source, 'payload');
    file_put_contents($target, 'payload');

    $adapter = new Bz2Adapter($source);

    $adapter->rename($source, $target);
})->throws(RuntimeException::class, 'Target file');

it('throws when the source file is not readable on rename', function (): void {
    $source = $this->tempDir . '/source.bz2';
    $target = $this->tempDir . '/target.bz2';
    file_put_contents($source, 'payload');
    chmod($source, 0000);

    if (is_readable($source)) {
        $this->markTestSkipped('File permissions do not allow simulating an unreadable source.');
    }

    $adapter = new Bz2Adapter($source);

    $adapter->rename($source, $target);

    chmod($source, 0644);
})->throws(RuntimeException::class, 'not readable');

it('throws when the target directory is not writable on rename', function (): void {
    $source = $this->tempDir . '/source.bz2';
    $target = $this->tempDir . '/locked/target.bz2';
    $lockedDir = $this->tempDir . '/locked';
    mkdir($lockedDir, 0000, true);
    file_put_contents($source, 'payload');

    if (is_writable($lockedDir)) {
        $this->markTestSkipped('Directory permissions do not allow simulating a locked directory.');
    }

    $adapter = new Bz2Adapter($source);

    try {
        $adapter->rename($source, $target);
        $this->fail('Expected RuntimeException to be thrown');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain($lockedDir);
    }
});

it('throws when the target path is empty on rename', function (): void {
    $source = $this->tempDir . '/source.bz2';
    file_put_contents($source, 'payload');

    $adapter = new Bz2Adapter($source);

    $adapter->rename($source, '');
})->throws(RuntimeException::class, 'Cannot write to the directory of the target file');

it('throws when the file does not exist on read', function (): void {
    $adapter = new Bz2Adapter($this->tempDir . '/missing.bz2');

    set_error_handler(static function (int $severity, string $message): bool {
        return str_contains($message, 'file_get_contents');
    });

    try {
        $adapter->read('');
    } finally {
        restore_error_handler();
    }
})->throws(RuntimeException::class, 'Failed to read the file');

it('throws when the target path is not writable on write', function (): void {
    $target = $this->tempDir . '/a-directory';
    mkdir($target, 0777);

    $adapter = new Bz2Adapter($target);

    set_error_handler(static function (int $severity, string $message): bool {
        return str_contains($message, 'file_put_contents');
    });

    try {
        $adapter->write('', 'payload');
    } finally {
        restore_error_handler();
    }
})->throws(RuntimeException::class, 'Failed to write to the file');

it('throws when the underlying rename operation fails', function (): void {
    $source = $this->tempDir . '/source.bz2';
    file_put_contents($source, 'payload');

    $adapter = new Bz2Adapter($source);

    set_error_handler(static function (int $severity, string $message): bool {
        return str_contains($message, 'rename');
    });

    try {
        $adapter->rename($source, $source . '/child');
    } finally {
        restore_error_handler();
    }
})->throws(RuntimeException::class, 'Failed to rename');

/**
 * Returns the bz2File property value from the adapter.
 */
function readProperty(Bz2Adapter $adapter): string
{
    $reflection = new \ReflectionProperty(Bz2Adapter::class, 'bz2File');
    $value = $reflection->getValue($adapter);

    if (!is_string($value)) {
        throw new \UnexpectedValueException('Expected the bz2File property to be a string.');
    }

    return $value;
}

/**
 * Recursively removes a directory and all of its contents.
 */
function bz2RemoveDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    @chmod($directory, 0755);
    $items = scandir($directory);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = slash($directory . '/' . $item);

        if (is_dir($path)) {
            bz2RemoveDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($directory);
}
