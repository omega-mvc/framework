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

use Omega\Archive\NativePharEngine;
use Omega\Archive\PharAdapter;
use RuntimeException;
use Tests\FixturesPathTrait;

use function is_dir;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function scandir;
use function str_contains;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

covers(
    PharAdapter::class,
    NativePharEngine::class,
);

uses(FixturesPathTrait::class);

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir() . '/omega-archive-phar-' . uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    pharAdapterRemoveDirectory($this->tempDir);
});

it('accepts an existing Phar archive in the constructor', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    expect($adapter->read('hello.txt'))->toBe('hello phar payload');
});

it('throws when the file does not exist in the constructor', function (): void {
    new PharAdapter($this->tempDir . '/missing.phar');
})->throws(RuntimeException::class, 'does not exist');

it('accepts an existing file on open', function (): void {
    $sample = $this->setFixturePath('/fixtures/archive/sample.phar');
    $adapter = new PharAdapter($sample);

    $adapter->open($sample);

    expect($adapter->read('hello.txt'))->toBe('hello phar payload');
});

it('throws when the file does not exist on open', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    $adapter->open($this->tempDir . '/missing.phar');
})->throws(RuntimeException::class, 'does not exist');

it('throws when the phar archive is corrupt on open', function (): void {
    $file = $this->tempDir . '/corrupt.phar';
    file_put_contents($file, 'this is plain text, not a phar archive');

    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    $adapter->open($file);
})->throws(RuntimeException::class, 'Failed to open Phar archive');

it('close is callable without throwing', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    $adapter->close();

    expect($adapter->exists('hello.txt'))->toBeTrue();
});

it('returns the stored content of a member on read', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    expect($adapter->read('hello.txt'))->toBe('hello phar payload');
});

it('throws when the member does not exist on read', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    $adapter->read('nope.txt');
})->throws(RuntimeException::class, 'does not exist in the Phar archive');

it('exists reflects membership in the archive', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    expect($adapter->exists('hello.txt'))->toBeTrue();
    expect($adapter->exists('missing.txt'))->toBeFalse();
});

it('returns the stream URIs of the archive members on keys', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    $keys = $adapter->keys();

    expect($keys)->toHaveCount(1);
    expect($keys[0])->toStartWith('phar://');
    expect($keys[0])->toContain('hello.txt');
});

it('isDirectory distinguishes directories from files', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    expect($adapter->isDirectory('hello.txt'))->toBeFalse();
});

it('throws when the key does not exist on isDirectory', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    $adapter->isDirectory('nope.txt');
})->throws(RuntimeException::class, 'does not exist');

it('returns an integer timestamp on mtime', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    expect($adapter->mtime('hello.txt'))->toBeInt();
});

it('throws when the member does not exist on mtime', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    $adapter->mtime('nope.txt');
})->throws(RuntimeException::class, 'does not exist');

it('write adds a member and returns its length', function (): void {
    $fake = new FakePharEngine();
    $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

    $length = $adapter->write('new.txt', 'abcde');

    expect($length)->toBe(5);
    expect($adapter->read('new.txt'))->toBe('abcde');
    expect($fake->state())->toBe(['new.txt' => 'abcde']);
});

it('delete removes a member', function (): void {
    $fake = new FakePharEngine(['hello.txt' => 'hello phar payload']);
    $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

    expect($adapter->delete('hello.txt'))->toBeTrue();
    expect($adapter->exists('hello.txt'))->toBeFalse();
});

it('throws when the key does not exist on delete', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    $adapter->delete('nope.txt');
})->throws(RuntimeException::class, 'does not exist and cannot be deleted');

it('rename moves a member and removes the source', function (): void {
    $fake = new FakePharEngine(['hello.txt' => 'hello phar payload']);
    $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

    expect($adapter->rename('hello.txt', 'renamed.txt'))->toBeTrue();
    expect($adapter->exists('hello.txt'))->toBeFalse();
    expect($adapter->exists('renamed.txt'))->toBeTrue();
    expect($adapter->read('renamed.txt'))->toBe('hello phar payload');
    expect($fake->state())->toBe(['renamed.txt' => 'hello phar payload']);
});

it('throws when the source member does not exist on rename', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    $adapter->rename('missing.txt', 'renamed.txt');
})->throws(RuntimeException::class, 'does not exist');

it('throws when the target member already exists on rename', function (): void {
    $adapter = new PharAdapter($this->setFixturePath('/fixtures/archive/sample.phar'));

    $adapter->rename('hello.txt', 'hello.txt');
})->throws(RuntimeException::class, 'already exists');

it('throws when the source is a directory member on rename', function (): void {
    $fake = new FailingPharEngine(['mydir' => '']);
    $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

    $adapter->rename('mydir', 'renamed');
})->throws(RuntimeException::class, 'Failed to rename');

it('throws when the content is unreadable on rename', function (): void {
    $fake = new UnreadablePharEngine(['hello.txt' => 'hello phar payload']);
    $adapter = new PharAdapter($this->tempDir . '/fake.phar', $fake);

    $adapter->rename('hello.txt', 'renamed.txt');
})->throws(RuntimeException::class, 'Failed to rename');

function pharAdapterRemoveDirectory(string $directory): void
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
            pharAdapterRemoveDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($directory);
}
