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

use Omega\Archive\ZipAdapter;
use RuntimeException;
use Tests\FixturesPathTrait;
use ZipArchive;

use function copy;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

covers(ZipAdapter::class);

uses(FixturesPathTrait::class);

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir() . '/omega-archive-zip-' . uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    zipRemoveDirectory($this->tempDir);
});

it('checks membership via exists', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    expect($adapter->exists('hello.txt'))->toBeTrue();
    expect($adapter->exists('missing.txt'))->toBeFalse();
});

it('returns the stored content of a member on read', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    expect($adapter->read('hello.txt'))->toBe('hello zip payload');
});

it('throws when the member does not exist on read', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    $adapter->read('nope.txt');
})->throws(RuntimeException::class, 'Failed to read the key');

it('write adds a member and returns its length', function (): void {
    $file = $this->tempDir . '/written.zip';
    copy($this->setFixturePath('/fixtures/archive/sample.zip'), $file);

    $adapter = new ZipAdapter($file);

    $length = $adapter->write('new.txt', 'abcde');

    expect($length)->toBe(5);

    $adapter->close();

    $reopened = new ZipAdapter($file);

    expect($reopened->read('new.txt'))->toBe('abcde');
});

it('delete removes a member', function (): void {
    $file = $this->tempDir . '/written.zip';
    copy($this->setFixturePath('/fixtures/archive/sample.zip'), $file);

    $adapter = new ZipAdapter($file);

    expect($adapter->delete('hello.txt'))->toBeTrue();

    $adapter->close();

    $reopened = new ZipAdapter($file);

    expect($reopened->exists('hello.txt'))->toBeFalse();
});

it('returns the member names on keys', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    expect($adapter->keys())->toBe(['hello.txt']);
});

it('returns an empty array for an empty archive on keys', function (): void {
    $file = $this->tempDir . '/empty.zip';

    $zip = new ZipArchive();
    $zip->open($file, ZipArchive::CREATE);
    $zip->close();

    $adapter = new ZipAdapter($file);

    expect($adapter->keys())->toBe([]);
});

it('isDirectory distinguishes directories from files', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    expect($adapter->isDirectory('hello.txt'))->toBeFalse();
    expect($adapter->isDirectory('some/dir/'))->toBeTrue();
});

it('returns an integer timestamp on mtime', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    expect($adapter->mtime('hello.txt'))->toBeInt();
});

it('returns false for a missing member on mtime', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    expect($adapter->mtime('missing.txt'))->toBeFalse();
});

it('rename persists the change to disk', function (): void {
    $file = $this->tempDir . '/rename.zip';
    copy($this->setFixturePath('/fixtures/archive/sample.zip'), $file);

    $adapter = new ZipAdapter($file);

    expect($adapter->rename('hello.txt', 'renamed.txt'))->toBeTrue();

    $adapter->close();

    $reopened = new ZipAdapter($file);

    expect($reopened->keys())->toBe(['renamed.txt']);
    expect($reopened->read('renamed.txt'))->toBe('hello zip payload');
    expect($reopened->exists('hello.txt'))->toBeFalse();
});

it('throws when the source member does not exist on rename', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    $adapter->rename('missing.txt', 'renamed.txt');
})->throws(RuntimeException::class, 'source file');

it('throws when the target member already exists on rename', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    $adapter->rename('hello.txt', 'hello.txt');
})->throws(RuntimeException::class, 'target file');

it('throws when the file is not a valid ZIP archive in the constructor', function (): void {
    $file = $this->tempDir . '/corrupt.zip';
    file_put_contents($file, 'PK');

    new ZipAdapter($file);
})->throws(RuntimeException::class, 'Unable to open the ZIP file');

it('throws when the archive enters an invalid state on keys', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    $adapter->delete('missing.txt');

    $adapter->keys();
})->throws(RuntimeException::class, 'is not open or is invalid');

it('throws when the underlying rename operation fails', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    $adapter->rename('hello.txt', 'foo/');
})->throws(RuntimeException::class, 'Failed to rename');

it('can re-open an adapter with a different archive on open', function (): void {
    $file = $this->tempDir . '/other.zip';
    copy($this->setFixturePath('/fixtures/archive/sample.zip'), $file);

    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    $adapter->open($file);

    expect($adapter->read('hello.txt'))->toBe('hello zip payload');
});

it('throws when the file is not a valid ZIP archive on open', function (): void {
    $file = $this->tempDir . '/invalid.txt';
    file_put_contents($file, 'not a zip archive content at all');

    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    $adapter->open($file);
})->throws(RuntimeException::class, 'Unable to open the ZIP file');

it('close is callable without throwing', function (): void {
    $adapter = new ZipAdapter($this->setFixturePath('/fixtures/archive/sample.zip'));

    $adapter->close();

    expect(file_exists($this->setFixturePath('/fixtures/archive/sample.zip')))->toBeTrue();
});

function zipRemoveDirectory(string $directory): void
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
            zipRemoveDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($directory);
}
