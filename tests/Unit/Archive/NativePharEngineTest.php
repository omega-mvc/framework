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
use PharData;

use function is_dir;
use function mkdir;
use function Omega\Application\slash;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

covers(NativePharEngine::class);

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir() . '/omega-archive-native-' . uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    nativePharRemoveDirectory($this->tempDir);
});

it('write adds a new member which can then be read', function (): void {
    $engine = new NativePharEngine(
        new PharData($this->tempDir . '/archive.tar')
    );

    $engine->write('hello.txt', 'hello phar payload');

    expect($engine->contains('hello.txt'))->toBeTrue();
    expect($engine->read('hello.txt'))->toBe('hello phar payload');
});

it('delete removes an existing member', function (): void {
    $engine = new NativePharEngine(
        new PharData($this->tempDir . '/archive.tar')
    );
    $engine->write('hello.txt', 'hello phar payload');

    $engine->delete('hello.txt');

    expect($engine->contains('hello.txt'))->toBeFalse();
});

it('delete after write leaves the archive in the expected state', function (): void {
    $engine = new NativePharEngine(
        new PharData($this->tempDir . '/archive.tar')
    );
    $engine->write('a.txt', 'alpha');
    $engine->write('b.txt', 'beta');

    $engine->delete('a.txt');

    expect($engine->contains('a.txt'))->toBeFalse();
    expect($engine->contains('b.txt'))->toBeTrue();
    expect($engine->read('b.txt'))->toBe('beta');
});

it('isDirectory distinguishes directories from files', function (): void {
    $engine = new NativePharEngine(
        new PharData($this->tempDir . '/archive.tar')
    );
    $engine->write('mydir/file.txt', 'nested');

    expect($engine->isDirectory('mydir/file.txt'))->toBeFalse();
    expect($engine->isDirectory('mydir'))->toBeTrue();
});

function nativePharRemoveDirectory(string $directory): void
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
            nativePharRemoveDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($directory);
}
