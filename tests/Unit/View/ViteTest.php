<?php

declare(strict_types=1);

namespace Tests\View;

use Exception;
use Omega\View\Vite;
use Tests\FixturesPathTrait;

use function chmod;
use function dirname;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;

uses(FixturesPathTrait::class);

covers(Vite::class);

afterEach(function (): void {
    Vite::flush();
});

it('can get file resource name', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

    $file = $asset->get('resources/css/app.css');

    expect($file)->toEqual('build/fixtures/app-4ed993c7.css');
});

it('can get file resource names', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

    $files = $asset->gets([
        'resources/css/app.css',
        'resources/js/app.js',
    ]);

    expect($files)->toEqual([
        'resources/css/app.css' => 'build/fixtures/app-4ed993c7.css',
        'resources/js/app.js'   => 'build/fixtures/app-0d91dc04.js',
    ]);
});

it('can check running hrm exist', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

    expect($asset->isRunningHRM())->toBeTrue();
});

it('can check running hrm does exist', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

    expect($asset->isRunningHRM())->toBeFalse();
});

it('can get hot file resource name', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

    $file = $asset->get('resources/css/app.css');

    expect($file)->toEqual('http://[::1]:5173/resources/css/app.css');
});

it('can get hot file resource names', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

    $files = $asset->gets([
        'resources/css/app.css',
        'resources/js/app.js',
    ]);

    expect($files)->toEqual([
        'resources/css/app.css' => 'http://[::1]:5173/resources/css/app.css',
        'resources/js/app.js'   => 'http://[::1]:5173/resources/js/app.js',
    ]);
});

it('can use cache', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');
    $asset->get('resources/css/app.css');

    expect(Vite::$cache)->toHaveCount(1);
});

it('can get hot url', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

    expect($asset->getHmrUrl())->toEqual(
        'http://[::1]:5173/'
    );
});

it('can get hmr script', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

    expect($asset->getHmrScript())->toEqual(
        '<script type="module" src="http://[::1]:5173/@vite/client"></script>'
    );
});

it('invoke returns empty string when no entry points are provided', function (): void {
    $vite = new Vite(__DIR__, 'build');

    $result = $vite();

    expect($result)->toBe('');
});

it('uses custom manifest name', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

    $vite->manifestName('custom-manifest.json');

    expect($vite->manifest())->toEndWith(
        'custom-manifest.json'
    );
});

it('throws exception if manifest file not found', function (): void {
    $this->expectException(Exception::class);
    $this->expectExceptionMessageIsOrContains('Manifest file not found');

    $vite = new Vite(__DIR__ . '/fixtures', 'build');

    // Nome volutamente inesistente
    $vite->manifestName('does-not-exist.json');

    $vite->manifest();
});

it('manifest throws exception if file does not exist', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build');
    $vite->manifestName('nonexistent.json');

    $this->expectException(Exception::class);
    $this->expectExceptionMessageMatches('/Manifest file not found/');

    $vite->manifest();
});

it('loader throws exception if file cannot be read', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build');
    $vite->manifestName('unreadable.json');

    $filePath = $this->setFixturePath('/fixtures/support/manifest/public/build/unreadable.json');
    if (!is_dir(dirname($filePath))) {
        mkdir(dirname($filePath), 0777, true);
    }

    file_put_contents($filePath, '{"key":"value"}');
    chmod($filePath, 0000);

    $this->expectException(Exception::class);
    $this->expectExceptionMessageMatches('/Failed to read manifest file/');

    try {
        @$vite->loader();
    } finally {
        chmod($filePath, 0644);
    }
});

it('loader throws exception on invalid json', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build');
    $vite->manifestName('invalid.json');

    $filePath = $this->setFixturePath('/fixtures/support/manifest/public/build/invalid.json');
    if (!is_dir(dirname($filePath))) {
        mkdir(dirname($filePath), 0777, true);
    }

    file_put_contents($filePath, '{invalid json');

    $this->expectException(Exception::class);
    $this->expectExceptionMessageMatches('/Manifest JSON decode error/');

    $vite->loader();
});

it('can get the manifest path for a specific resource', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

    $path = $asset->getManifest('resources/js/app.js');

    expect($path)->toEqual('build/fixtures/app-0d91dc04.js');
});

it('throws exception if resource not found in manifest', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

    $this->expectException(Exception::class);
    $this->expectExceptionMessageIsOrContains('Resource file not found non-existent-file.js');

    $asset->getManifest('non-existent-file.js');
});

it('returns cached hot url on second call', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

    $url1 = $asset->getHmrUrl();
    $url2 = $asset->getHmrUrl();

    expect($url1)->toEqual($url2);
    expect(Vite::$hot)->not->toBeNull();
});

it('throws exception if hot file is unreadable', function (): void {
    $asset = new Vite($this->setFixturePath('/fixtures/application-write/public/'), 'build/');

    $this->expectException(Exception::class);
    $this->expectExceptionMessageIsOrContains('Failed to read hot file');

    $asset->getHmrUrl();
});

it('handles hot file with trailing slash', function (): void {
    $tempDir = sys_get_temp_dir() . '/vite_test_' . uniqid();
    mkdir($tempDir);
    file_put_contents($tempDir . '/hot', "http://localhost:5173/\n");

    $asset = new Vite($tempDir, 'build/');
    $url = $asset->getHmrUrl();

    expect($url)->toEqual('http://localhost:5173/');

    unlink($tempDir . '/hot');
    rmdir($tempDir);
});

it('updates and returns cache time', function (): void {
    $manifestDir = $this->setFixturePath('/fixtures/application-write/manifest/public/build');

    if (!is_dir($manifestDir)) {
        mkdir($manifestDir, 0777, true);
    }

    $manifestPath = "{$manifestDir}/manifest.json";
    file_put_contents($manifestPath, json_encode([
        'resources/js/app.js' => ['file' => 'app.js']
    ]));

    $expectedTime = time() - 3600;
    touch($manifestPath, $expectedTime);

    $vite = new Vite($this->setFixturePath('/fixtures/application-write/manifest/public'), 'build');

    expect($vite->cacheTime())->toEqual(0);

    $vite->loader();

    expect($vite->cacheTime())->toEqual($expectedTime);
    expect($vite->manifestTime())->toEqual($expectedTime);

    unlink($manifestPath);
    rmdir($manifestDir);
});

it('get preload tags returns empty string when hmr is running', function (): void {
    $publicPath = $this->setFixturePath('/fixtures/application-write/manifest/public');
    if (!is_dir($publicPath)) {
        @mkdir($publicPath, 0777, true);
    }

    file_put_contents("{$publicPath}/hot", 'http://localhost:3000');

    $vite = new Vite($publicPath, 'build');

    $result = $vite->getPreloadTags(['main.js']);

    expect($result)->toBe('');

    unlink("{$publicPath}/hot");
    rmdir($publicPath);
});
