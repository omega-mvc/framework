<?php

declare(strict_types=1);

namespace Tests\Container;

use Exception;
use Omega\Container\AbstractServiceProvider;
use Tests\FixturesPathTrait;

use function chmod;
use function copy;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function unlink;

covers(AbstractServiceProvider::class);

uses(FixturesPathTrait::class);

beforeEach(function (): void {
    $this->basePath = $this->setFixtureBasePath();
});

it('creates the destination directory when importing a file', function (): void {
    $source = $this->basePath . '/fixtures/application-write/copy/from/file.txt';
    $target = $this->basePath . '/fixtures/application-write/tmp/newdir/file.txt';

    $dir = dirname($target);

    if (file_exists($target)) {
        unlink($target);
    }

    if (is_dir($dir)) {
        rmdir($dir);
    }

    $result = AbstractServiceProvider::importFile($source, $target, true);

    expect($result)->toBeTrue();
    expect(file_exists($target))->toBeTrue();
    expect(is_dir($dir))->toBeTrue();

    unlink($target);
    rmdir($dir);
});

it('overwrites an existing file when allowed', function (): void {
    $source = $this->basePath . '/fixtures/application-write/copy/from/file.txt';
    $target = $this->basePath . '/fixtures/application-write/tmp/existing/file.txt';

    $dir = dirname($target);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    copy($source, $target);

    $result = AbstractServiceProvider::importFile($source, $target, true);

    expect($result)->toBeTrue();
    expect(file_exists($target))->toBeTrue();

    unlink($target);
    rmdir($dir);
});

it('returns false when the file copy fails', function (): void {
    $source = $this->basePath . '/non_existent_file.txt';
    $target = $this->basePath . '/fixtures/application-write/tmp/target.txt';

    $result = @AbstractServiceProvider::importFile($source, $target, true);

    expect($result)->toBeFalse();
});

it('returns false when the source directory does not exist', function (): void {
    $nonExistentDir = $this->basePath . '/directory_that_never_exists';
    $targetDir      = $this->basePath . '/fixtures/application-write/tmp/target';

    $result = @AbstractServiceProvider::importDir($nonExistentDir, $targetDir);

    expect($result)->toBeFalse();
});

it('handles recursion when importing a directory', function (): void {
    $sourceDir  = $this->basePath . '/fixtures/application-write/recursive_test';
    $subDir     = $sourceDir . '/subdir';
    $sourceFile = $subDir . '/test.txt';

    if (!is_dir($subDir)) {
        mkdir($subDir, 0755, true);
    }

    file_put_contents($sourceFile, 'omega content');

    $targetDir = $this->basePath . '/fixtures/application-write/recursive_target';

    $result = AbstractServiceProvider::importDir($sourceDir, $targetDir, true);

    expect($result)->toBeTrue();
    expect(file_exists($targetDir . '/subdir/test.txt'))->toBeTrue();

    @unlink($sourceFile);
    @rmdir($subDir);
    @rmdir($sourceDir);
});

it('returns false when scandir fails on a readable dir', function (): void {
    $protectedDir = $this->basePath . '/fixtures/application-write/inaccessible_dir';
    mkdir($protectedDir, 0755, true);

    chmod($protectedDir, 0333);

    $result = @AbstractServiceProvider::importDir($protectedDir, $this->basePath . '/target');

    expect($result)->toBeFalse();

    chmod($protectedDir, 0755);
    rmdir($protectedDir);
});

it('imports a file into an existing directory', function (): void {
    $targetDir = $this->basePath . '/fixtures/application-write/tmp/exists';

    mkdir($targetDir, 0755, true);

    $source = $this->basePath . '/fixtures/application-write/copy/from/file.txt';
    $target = $targetDir . '/newfile.txt';

    $result = AbstractServiceProvider::importFile($source, $target, true);

    expect($result)->toBeTrue();

    unlink($target);
    rmdir($targetDir);
});

it('throws when the file exists and overwrite is not allowed', function (): void {
    $source = $this->basePath . '/fixtures/application-write/copy/from/file.txt';
    $target = $this->basePath . '/fixtures/application-write/copy/to/existing-file.txt';

    if (!file_exists(dirname($target))) {
        mkdir(dirname($target), 0755, true);
    }

    copy($source, $target);

    expect(fn () => AbstractServiceProvider::importFile($source, $target, false))
        ->toThrow(Exception::class, 'You do not have permission to overwrite the destination file.');

    @unlink($target);
});