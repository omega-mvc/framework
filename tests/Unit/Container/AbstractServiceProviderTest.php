<?php

declare(strict_types=1);

namespace Tests\Container;

use Exception;
use Omega\Application\Application;
use Omega\Application\ApplicationInterface;
use Omega\Container\AbstractServiceProvider;

use function chmod;
use function copy;
use function dirname;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fopen;
use function is_dir;
use function mkdir;
use function rmdir;
use function unlink;

covers(AbstractServiceProvider::class);


beforeEach(function (): void {
    $this->basePath = __DIR__ . '/fixtures';

    // The provider keeps a static module registry; flush it so the export
    // tests below never observe stale state from a previous test run.
    AbstractServiceProvider::flushModule();
});

it('creates the destination directory when importing a file', function (): void {
    $source = $this->basePath . '/copy/from/file.txt';
    $target = $this->basePath . '/tmp/newdir/file.txt';

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
    $source = $this->basePath . '/copy/from/file.txt';
    $target = $this->basePath . '/tmp/existing/file.txt';

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
    $target = $this->basePath . '/tmp/target.txt';

    $result = @AbstractServiceProvider::importFile($source, $target, true);

    expect($result)->toBeFalse();
});

it('returns false when the source directory does not exist', function (): void {
    $nonExistentDir = $this->basePath . '/directory_that_never_exists';
    $targetDir      = $this->basePath . '/tmp/target';

    $result = @AbstractServiceProvider::importDir($nonExistentDir, $targetDir);

    expect($result)->toBeFalse();
});

it('handles recursion when importing a directory', function (): void {
    $sourceDir  = $this->basePath . '/recursive_test';
    $subDir     = $sourceDir . '/subdir';
    $sourceFile = $subDir . '/test.txt';

    if (!is_dir($subDir)) {
        mkdir($subDir, 0755, true);
    }

    file_put_contents($sourceFile, 'omega content');

    $targetDir = $this->basePath . '/recursive_target';

    $result = AbstractServiceProvider::importDir($sourceDir, $targetDir, true);

    expect($result)->toBeTrue();
    expect(file_exists($targetDir . '/subdir/test.txt'))->toBeTrue();

    @unlink($sourceFile);
    @rmdir($subDir);
    @rmdir($sourceDir);
});

it('returns false when scandir fails on a readable dir', function (): void {
    $protectedDir = $this->basePath . '/inaccessible_dir';
    mkdir($protectedDir, 0755, true);

    chmod($protectedDir, 0333);

    $result = @AbstractServiceProvider::importDir($protectedDir, $this->basePath . '/target');

    expect($result)->toBeFalse();

    chmod($protectedDir, 0755);
    rmdir($protectedDir);
});

it('imports a file into an existing directory', function (): void {
    $targetDir = $this->basePath . '/tmp/exists';

    mkdir($targetDir, 0755, true);

    $source = $this->basePath . '/copy/from/file.txt';
    $target = $targetDir . '/newfile.txt';

    $result = AbstractServiceProvider::importFile($source, $target, true);

    expect($result)->toBeTrue();

    unlink($target);
    rmdir($targetDir);
});

it('throws when the file exists and overwrite is not allowed', function (): void {
    $source = $this->basePath . '/copy/from/file.txt';
    $target = $this->basePath . '/copy/to/existing-file.txt';

    if (!file_exists(dirname($target))) {
        mkdir(dirname($target), 0755, true);
    }

    copy($source, $target);

    expect(fn () => AbstractServiceProvider::importFile($source, $target, false))
        ->toThrow(Exception::class, 'You do not have permission to overwrite the destination file.');

    @unlink($target);
});

it('registers module exports grouped by tag', function (): void {
    AbstractServiceProvider::export(['module' => '/modules/module'], 'plugins');
    AbstractServiceProvider::export(['other' => '/modules/other'], 'plugins');

    $modules = AbstractServiceProvider::getModules();

    expect($modules)->toHaveKey('plugins');
    expect($modules['plugins'])->toBe([
        'module' => '/modules/module',
        'other'  => '/modules/other',
    ]);
});

it('creates a new module registry tag on first export', function (): void {
    AbstractServiceProvider::export(['module' => '/modules/module']);

    expect(AbstractServiceProvider::getModules())->toBe([
        '' => ['module' => '/modules/module'],
    ]);
});

it('flushes the shared module registry', function (): void {
    AbstractServiceProvider::export(['module' => '/modules/module']);

    expect(AbstractServiceProvider::getModules())->not->toBeEmpty();

    AbstractServiceProvider::flushModule();

    expect(AbstractServiceProvider::getModules())->toBeEmpty();
});

it('aborts a directory import as soon as one file fails', function (): void {
    $sourceDir = $this->basePath . '/tmp/importdir_abort/src';
    $targetDir = $this->basePath . '/tmp/importdir_abort/tgt';

    // Remove any leftovers from an interrupted previous run before starting.
    foreach (['a_fail.txt', 'z_ok.txt'] as $file) {
        $path = $sourceDir . '/' . $file;

        if (file_exists($path)) {
            @chmod($path, 0644);
            @unlink($path);
        }
    }

    if (is_dir($sourceDir)) {
        @rmdir($sourceDir);
    }

    mkdir($sourceDir, 0755, true);

    $failing = $sourceDir . '/a_fail.txt';
    $ok      = $sourceDir . '/z_ok.txt';
    file_put_contents($failing, 'failing content');
    file_put_contents($ok, 'ok content');

    chmod($failing, 0000);

    // The first (alphabetically) file is unreadable, so the import stops
    // before the second, perfectly valid file is ever processed.
    $result = AbstractServiceProvider::importDir($sourceDir, $targetDir, true);

    expect($result)->toBeFalse();
    expect(file_exists($targetDir . '/z_ok.txt'))->toBeFalse();

    chmod($failing, 0644);
    @unlink($failing);
    @unlink($ok);
    @rmdir($sourceDir);
    @rmdir($targetDir);
    @rmdir(dirname($sourceDir));
});

it('returns false when the file copy fails after the directory exists', function (): void {
    $source = $this->basePath . '/copy/from/file.txt';
    $dir    = $this->basePath . '/tmp/copy_fail_dir';

    if (is_dir($dir)) {
        @chmod($dir, 0755);
        @rmdir($dir);
    }

    mkdir($dir, 0755, true);
    chmod($dir, 0444);

    set_error_handler(static fn (): bool => true);

    try {
        // The destination directory is readable but not writable: the copy
        // step fails, which is reported as a false result, not an exception.
        $result = AbstractServiceProvider::importFile($source, $dir . '/dest.txt', true);
    } finally {
        restore_error_handler();
    }

    expect($result)->toBeFalse();

    chmod($dir, 0755);
    @rmdir($dir);
});

it('exposes the application passed to the provider constructor', function (): void {
    $app = new Application(__DIR__);

    try {
        $provider = new class ($app) extends AbstractServiceProvider {
            public function getApp(): ApplicationInterface
            {
                return $this->app;
            }

            /** @return array<int|string, class-string> */
            public function getRegister(): array
            {
                return $this->register;
            }
        };

        expect($provider->getApp())->toBe($app);

        // The base boot() and register() hooks do nothing and stay chainable.
        $provider->boot();
        $provider->register();

        expect($provider->getRegister())->toBe([]);
    } finally {
        // Release the static Application::$app reference and its bindings so
        // the persistent container does not leak into sibling tests.
        $app->flush();
    }
});

it('copies into an already existing destination directory', function (): void {
    $sourceDir = $this->basePath . '/tmp/importdir_existing/src';
    $targetDir = $this->basePath . '/tmp/importdir_existing/tgt';

    // Remove any leftovers from an interrupted previous run before starting.
    foreach ([$sourceDir, $targetDir] as $dir) {
        foreach (['a.txt', 'b.txt'] as $file) {
            $path = $dir . '/' . $file;

            if (file_exists($path)) {
                @chmod($path, 0644);
                @unlink($path);
            }
        }

        if (is_dir($dir)) {
            @rmdir($dir);
        }
    }

    mkdir($sourceDir, 0755, true);
    mkdir($targetDir, 0755, true);

    file_put_contents($sourceDir . '/a.txt', 'new content');
    file_put_contents($sourceDir . '/b.txt', 'extra content');
    file_put_contents($targetDir . '/a.txt', 'stale content');

    // The destination directory already exists, so the import reuses it and
    // overwrites the stale 'a.txt' with the fresh source content.
    $result = AbstractServiceProvider::importDir($sourceDir, $targetDir, true);

    expect($result)->toBeTrue();
    expect(file_get_contents($targetDir . '/a.txt'))->toBe('new content');
    expect(file_exists($targetDir . '/b.txt'))->toBeTrue();

    @chmod($targetDir . '/a.txt', 0644);
    @chmod($targetDir . '/b.txt', 0644);
    @unlink($targetDir . '/a.txt');
    @unlink($targetDir . '/b.txt');
    @rmdir($targetDir);
    @unlink($sourceDir . '/a.txt');
    @unlink($sourceDir . '/b.txt');
    @rmdir($sourceDir);
    @rmdir(dirname($sourceDir));
});

it('aborts when a nested subdirectory import fails', function (): void {
    $sourceDir = $this->basePath . '/tmp/importdir_nested/src';
    $subDir    = $sourceDir . '/sub';
    $locked    = $subDir . '/locked.txt';
    $targetDir = $this->basePath . '/tmp/importdir_nested/tgt';

    // Remove any leftovers from an interrupted previous run before starting.
    if (file_exists($locked)) {
        @chmod($locked, 0644);
        @unlink($locked);
    }

    foreach ([$subDir, $sourceDir] as $dir) {
        if (is_dir($dir)) {
            @rmdir($dir);
        }
    }

    mkdir($subDir, 0755, true);
    file_put_contents($locked, 'locked content');
    chmod($locked, 0000);

    // The recursion into 'sub' hits the unreadable file and reports failure,
    // which then aborts the whole directory import.
    $result = AbstractServiceProvider::importDir($sourceDir, $targetDir, true);

    expect($result)->toBeFalse();
    expect(file_exists($targetDir . '/sub/locked.txt'))->toBeFalse();

    chmod($locked, 0644);
    @unlink($locked);
    @rmdir($subDir);
    @rmdir($sourceDir);
    @rmdir($targetDir . '/sub');
    @rmdir($targetDir);
    @rmdir(dirname($sourceDir));
});

it('imports an empty source directory creating the destination', function (): void {
    $sourceDir = $this->basePath . '/tmp/importdir_empty/src';
    $targetDir = $this->basePath . '/tmp/importdir_empty/tgt';

    if (is_dir($sourceDir)) {
        @rmdir($sourceDir);
    }

    if (is_dir($targetDir)) {
        @rmdir($targetDir);
    }

    mkdir($sourceDir, 0755, true);

    // Nothing to copy, the empty listing still resolves to a successful import.
    $result = AbstractServiceProvider::importDir($sourceDir, $targetDir);

    expect($result)->toBeTrue();
    expect(is_dir($targetDir))->toBeTrue();

    @rmdir($targetDir);
    @rmdir($sourceDir);
    @rmdir(dirname($sourceDir));
});

it('records an empty module export under a fresh tag', function (): void {
    AbstractServiceProvider::export([], 'empty');

    expect(AbstractServiceProvider::getModules())->toBe(['empty' => []]);
});

it('lets a later export override an earlier mapping for the same key', function (): void {
    AbstractServiceProvider::export(['asset' => '/modules/asset.css'], 'assets');
    AbstractServiceProvider::export(['asset' => '/modules/asset.min.css'], 'assets');

    expect(AbstractServiceProvider::getModules())->toBe([
        'assets' => ['asset' => '/modules/asset.min.css'],
    ]);
});

it('throws when a destination file exists and overwrite is disabled during a directory import', function (): void {
    $sourceDir = $this->basePath . '/tmp/importdir_throw/src';
    $targetDir = $this->basePath . '/tmp/importdir_throw/tgt';

    // Remove any leftovers from an interrupted previous run before starting.
    foreach ([$sourceDir, $targetDir] as $dir) {
        $path = $dir . '/c.txt';

        if (file_exists($path)) {
            @chmod($path, 0644);
            @unlink($path);
        }

        if (is_dir($dir)) {
            @rmdir($dir);
        }
    }

    mkdir($sourceDir, 0755, true);
    mkdir($targetDir, 0755, true);
    file_put_contents($sourceDir . '/c.txt', 'fresh content');
    file_put_contents($targetDir . '/c.txt', 'stale content');

    // The guard inside importFile() fires even when it is reached through the
    // reduce loop of a directory import.
    expect(fn () => AbstractServiceProvider::importDir($sourceDir, $targetDir, false))
        ->toThrow(Exception::class, 'You do not have permission to overwrite the destination file.');

    @chmod($targetDir . '/c.txt', 0644);
    @unlink($targetDir . '/c.txt');
    @rmdir($targetDir);
    @unlink($sourceDir . '/c.txt');
    @rmdir($sourceDir);
    @rmdir(dirname($sourceDir));
});

it('returns false when scandir runs out of descriptors on a readable directory', function (): void {
    $sourceFile = $this->basePath . '/copy/from/file.txt';
    $sourceDir = $this->basePath . '/copy';
    $targetDir = $this->basePath . '/tmp/emfile_import';

    // Sit on the descriptor table so the guarded scandir() inside importDir()
    // fails on a directory that is still perfectly readable. A throwaway
    // error handler swallows the expected E_WARNINGs so PHPUnit never tries
    // to render them (and therefore autoload classes) while no descriptor is
    // left to open anything with.
    set_error_handler(static fn (): bool => true);

    $rlimits = posix_getrlimit();

    if (false === $rlimits) {
        restore_error_handler();
        $this->fail('Expected posix_getrlimit() to report the current descriptor limits.');
    }

    $result = true;

    $handles = [];

    try {
        posix_setrlimit(POSIX_RLIMIT_NOFILE, 64, (int) $rlimits['hard openfiles']);

        while (true) {
            $handle = @fopen($sourceFile, 'rb');

            if (false === $handle) {
                break;
            }

            $handles[] = $handle;
        }

        $result = AbstractServiceProvider::importDir($sourceDir, $targetDir, true);
    } finally {
        foreach ($handles as $handle) {
            @fclose($handle);
        }

        posix_setrlimit(
            POSIX_RLIMIT_NOFILE,
            (int) $rlimits['soft openfiles'],
            (int) $rlimits['hard openfiles'],
        );

        restore_error_handler();
    }

    expect($result)->toBeFalse();
});
