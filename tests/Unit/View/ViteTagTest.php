<?php

declare(strict_types=1);

namespace Tests\View;

use Exception;
use Omega\View\Vite;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Tests\FixturesPathTrait;

use function file_put_contents;
use function mkdir;
use function unlink;

uses(FixturesPathTrait::class);

covers(Vite::class);

afterEach(function (): void {
    Vite::flush();
});

it('escape url', function (): void {
    $vite   = new Vite(__DIR__, '');
    $escape = (fn (string $url) => $this->{'escapeUrl'}($url))->call($vite, 'foo"bar');
    $this->assertEquals('foo&quot;bar', $escape);
    $escape2 = (fn (string $url) => $this->{'escapeUrl'}($url))->call($vite, 'https://example.com/path');
    $this->assertEquals('https://example.com/path', $escape2);
});

it('is css file', function (): void {
    $vite  = new Vite(__DIR__, '');
    $isCss = (fn (string $file) => $this->{'isCssFile'}($file))->call($vite, 'foo.css');
    $this->assertTrue($isCss);
    $isCss2 = (fn (string $file) => $this->{'isCssFile'}($file))->call($vite, 'bar.scss');
    $this->assertTrue($isCss2);
    $isCss3 = (fn (string $file) => $this->{'isCssFile'}($file))->call($vite, 'baz.js');
    $this->assertFalse($isCss3);
});

it('build attribute string', function (): void {
    $vite   = new Vite(__DIR__, '');

    $buildAttributeString = (fn () => $this->{'buildAttributeString'}([
        'data-foo'                => 123,
        'async'                   => 'true',
        'defer'                   => true,
        'false-should-be-ignored' => false,
        'null-should-be-ignored'  => null,
    ]))->call($vite);
    $this->assertEquals(
        'data-foo="123" async="true" defer',
        $buildAttributeString
    );
});

it('create style tag', function (): void {
    $vite = new Vite(__DIR__, '');

    $createStyleTag    = (fn () => $this->{'createStyleTag'}('foo.css'))->call($vite);
    $this->assertEquals('<link rel="stylesheet" href="foo.css">', $createStyleTag);
});

it('create script tag', function (): void {
    $vite   = new Vite(__DIR__, '');

    $createScriptTag    = (fn () => $this->{'createScriptTag'}('foo.js'))->call($vite);
    $this->assertEquals('<script type="module" src="foo.js"></script>', $createScriptTag);
});

it('create tag with attributes', function (): void {
    $vite   = new Vite(__DIR__, '');

    $createTagWithAttributes = (
        fn (
            string $url,
            string $entrypoint
        ) => $this->{'createTag'}($url, $entrypoint, [
            'data-foo' => 'bar',
            'async'    => 'true',
        ])
    )->call(
        $vite,
        'foo.js',
        'resources/js/app.js',
    );

    $this->assertEquals(
        '<script type="module" data-foo="bar" async="true" src="foo.js"></script>',
        $createTagWithAttributes
    );
});

it('create preload tag', function (): void {
    $vite = new Vite(__DIR__, '');

    $createPreloadTag    = (fn () => $this->{'createPreloadTag'}('foo.css'))->call($vite);
    $this->assertEquals('<link rel="modulepreload" href="foo.css">', $createPreloadTag);
});

it('get tags', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

    $tag = $vite->getTags(['resources/js/app.js', 'resources/css/app.css']);
    expect($tag)->toEqual(
        '<link rel="stylesheet" href="build/fixtures/app-4ed993c7.css">' . "\n" .
        '<script type="module" src="build/fixtures/app-0d91dc04.js"></script>'
    );
});

it('get tags attributes', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

    $tag = $vite->getTags(
        entryPoints: [
            'resources/js/app.js',
        ],
        attributes: [
            'defer' => true,
            'async' => 'true',
            'crossorigin',
        ],
    );

    expect($tag)->toEqual(
        '<script type="module" defer async="true" crossorigin src="build/fixtures/app-0d91dc04.js"></script>'
    );
});

it('get tags attributes with exception', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

    $tag = $vite->getCustomTags(
        entryPoints: [
            'resources/js/app.js' => [
                'defer' => true,
                'async' => 'true',
                'crossorigin',
            ],
            'resources/css/app.css' => [],
        ],
    );

    expect($tag)->toEqual(
        '<link rel="stylesheet" href="build/fixtures/app-4ed993c7.css">' . "\n" .
        '<script type="module" defer async="true" crossorigin src="build/fixtures/app-0d91dc04.js"></script>'
    );
});

it('get preload tags', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'preload/');

    $tag = $vite->getPreloadTags(['resources/js/app.js']);
    expect($tag)->toEqual(
        '<link rel="modulepreload" href="preload/fixtures/vendor.222bbb.js">' . "\n" .
        '<link rel="modulepreload" href="preload/fixtures/chunk-vue.333ccc.js">' . "\n" .
        '<link rel="modulepreload" href="preload/fixtures/chunk-utils.444ddd.js">' . "\n" .
        '<link rel="stylesheet" href="preload/fixtures/app.111aaa.css">'
    );
});

it('can render head html tag', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

    $headTag = $vite(
        'resources/css/app.css',
        'resources/js/app.js',
    );

    expect($headTag)->toEqual(
        '<link rel="stylesheet" href="build/fixtures/app-4ed993c7.css">' . "\n" .
        '<script type="module" src="build/fixtures/app-0d91dc04.js"></script>'
    );
});

it('can render head html tag in hrm mode', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

    $headTags = $vite(
        'resources/css/app.css',
        'resources/js/app.js'
    );

    expect($headTags)->toEqual(
        '<script type="module" src="http://[::1]:5173/@vite/client"></script>' . "\n" .
        '<script type="module" src="http://[::1]:5173/resources/css/app.css"></script>' . "\n" .
        '<script type="module" src="http://[::1]:5173/resources/js/app.js"></script>'
    );
});

it('can render head html tag with preload', function (): void {
    $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'preload/');

    $headTag = $vite('resources/js/app.js');

    expect($headTag)->toEqual(
        '<link rel="modulepreload" href="preload/fixtures/vendor.222bbb.js">' . "\n" .
        '<link rel="modulepreload" href="preload/fixtures/chunk-vue.333ccc.js">' . "\n" .
        '<link rel="modulepreload" href="preload/fixtures/chunk-utils.444ddd.js">' . "\n" .
        '<link rel="stylesheet" href="preload/fixtures/app.111aaa.css">' . "\n" .
        '<script type="module" src="preload/fixtures/app.111aaa.js"></script>'
    );
});

it('get custom tags with hmr', function (): void {
    $tmpDir = $this->setFixturePath('/fixtures/application-write/manifest1/public');
    if (!is_dir($tmpDir)) {
        @mkdir($tmpDir, 0777, true);
    }

    file_put_contents($tmpDir . '/hot', 'http://localhost:5173');

    $vite = new Vite($tmpDir, 'build');

    $entryPoints = [
        'app.js' => ['async' => true],
        'main.css' => []
    ];

    $tags = $vite->getCustomTags($entryPoints);

    expect($tags)->toContain('@vite/client');

    expect($tags)->toContain(
        '<script type="module" async src="http://localhost:5173/app.js"></script>'
    );

    expect($tags)->toContain(
        'http://localhost:5173/main.css'
    );

    unlink($tmpDir . '/hot');
});

it('build attribute string with empty attributes returns empty string', function (): void {
    $vite = new Vite('/public', '/build');

    $reflection = new ReflectionClass($vite);
    $method = $reflection->getMethod('buildAttributeString');
    $method->setAccessible(true);

    $result = $method->invoke($vite, []);

    expect($result)->toBe('');
});

it('create script tag with type already set', function (): void {
    $vite = new Vite('/public', '/build');

    $attributes = ['type' => 'text/javascript', 'async' => true];

    $result = invokeCreateScriptTag($vite, 'app.js', $attributes);

    expect($result)->toContain('type="text/javascript"');
    expect($result)->toContain('src="app.js"');
    expect($result)->toContain('async');

    expect($result)->toStartWith('<script ');
    expect($result)->toEndWith('</script>');
});

/**
 * @param array<int|string, bool|int|string|null>|null $attributes
 */
function invokeCreateScriptTag(Vite $vite, string $url, ?array $attributes = null): string
{
    $method = new ReflectionMethod(Vite::class, 'createScriptTag');
    $method->setAccessible(true);

    /** @var string $result */
    $result = $method->invoke($vite, $url, $attributes);

    return $result;
}
