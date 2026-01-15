<?php

declare(strict_types=1);

namespace Tests\Support;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Support\Vite;

#[CoversClass(Vite::class)]
final class ViteTagTest extends TestCase
{
    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Vite::flush();
    }

    /**
     * Test escape url.
     *
     * @return void
     */
    public function testEscapeUrl(): void
    {
        $vite   = new Vite(__DIR__, '');
        $escape = (fn ($url) => $this->{'escapeUrl'}($url))->call($vite, 'foo"bar');
        $this->assertEquals('foo&quot;bar', $escape, 'this must return escaped url for double quote');
        $escape2 = (fn ($url) => $this->{'escapeUrl'}($url))->call($vite, 'https://example.com/path');
        $this->assertEquals('https://example.com/path', $escape2, 'this must return escaped url for normal url');
    }

    /**
     * Test is CSS file.
     *
     * @return void
     */
    public function testIsCssFile(): void
    {
        $vite  = new Vite(__DIR__, '');
        $isCss = (fn ($file) => $this->{'isCssFile'}($file))->call($vite, 'foo.css');
        $this->assertTrue($isCss, 'should detect .css as css file');
        $isCss2 = (fn ($file) => $this->{'isCssFile'}($file))->call($vite, 'bar.scss');
        $this->assertTrue($isCss2, 'should detect .scss as css file');
        $isCss3 = (fn ($file) => $this->{'isCssFile'}($file))->call($vite, 'baz.js');
        $this->assertFalse($isCss3, 'should not detect .js as css file');
    }

    /**
     * Test build attribute string.
     *
     * @return void
     */
    public function testBuildAttributeString(): void
    {
        $vite   = new Vite(__DIR__, '');

        $buildAttributeString    = (fn ($attributes) => $this->{'buildAttributeString'}($attributes))->call($vite, [
            'data-foo'                => 123,
            'async'                   => 'true',
            'defer'                   => true,
            'false-should-be-ignored' => false,
            'null-should-be-ignored'  => null,
        ]);
        $this->assertEquals(
            'data-foo="123" async="true" defer',
            $buildAttributeString,
            'should build attribute string from array'
        );
    }

    /**
     * Test create style tag.
     *
     * @return void
     */
    public function testCreateStyleTag(): void
    {
        $vite = new Vite(__DIR__, '');

        $createStyleTag    = (fn () => $this->{'createStyleTag'}('foo.css'))->call($vite);
        $this->assertEquals('<link rel="stylesheet" href="foo.css">', $createStyleTag);
    }

    /**
     * Test create script tag.
     *
     * @return void
     */
    public function testCreateScriptTag(): void
    {
        $vite   = new Vite(__DIR__, '');

        $createScriptTag    = (fn () => $this->{'createScriptTag'}('foo.js'))->call($vite);
        $this->assertEquals('<script type="module" src="foo.js"></script>', $createScriptTag);
    }

    /**
     * Test create tag with attributes.
     *
     * @return void
     */
    public function testCreateTagWithAttributes(): void
    {
        $vite   = new Vite(__DIR__, '');

        $createTagWithAttributes = (
            fn (
                string $url,
                string $entrypoint,
                array $attributes
            ) => $this->{'createTag'}($url, $entrypoint, $attributes)
        )->call(
            $vite,
            'foo.js',
            'resources/js/app.js',
            [
                'data-foo' => 'bar',
                'async'    => 'true',
            ],
        );

        $this->assertEquals(
            '<script type="module" data-foo="bar" async="true" src="foo.js"></script>',
            $createTagWithAttributes
        );
    }

    /**
     * Test create preload tag.
     *
     * @return void
     */
    public function testCreatePreloadTag(): void
    {
        $vite = new Vite(__DIR__, '');

        $createPreloadTag    = (fn () => $this->{'createPreloadTag'}('foo.css'))->call($vite);
        $this->assertEquals('<link rel="modulepreload" href="foo.css">', $createPreloadTag);
    }

    /**
     * Test fet tags.
     *
     * @return void
     * @throws Exception
     */
    public function testGetTags(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/manifest/public', 'build/');

        $tag = $vite->getTags(['resources/js/app.js', 'resources/css/app.css']);
        $this->assertEquals(
            '<link rel="stylesheet" href="build/fixtures/app-4ed993c7.css">' . "\n" .
            '<script type="module" src="build/fixtures/app-0d91dc04.js"></script>',
            $tag
        );
    }

    /**
     * Test get tags attributes.
     *
     * @return void
     * @throws Exception
     */
    public function testGetTagsAttributes(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/manifest/public', 'build/');

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

        $this->assertEquals(
            '<script type="module" defer async="true" crossorigin src="build/fixtures/app-0d91dc04.js"></script>',
            $tag
        );
    }

    /**
     * Test get tags attributes with exception.
     *
     * @return void
     * @throws Exception
     */
    public function testGetTagsAttributesWithException(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/manifest/public', 'build/');

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

        $this->assertEquals(
            '<link rel="stylesheet" href="build/fixtures/app-4ed993c7.css">' . "\n" .
            '<script type="module" defer async="true" crossorigin src="build/fixtures/app-0d91dc04.js"></script>',
            $tag
        );
    }

    /**
     * Test get preload tags.
     *
     * @return void
     * @throws Exception
     */
    public function testGetPreloadTags(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/manifest/public', 'preload/');

        $tag = $vite->getPreloadTags(['resources/js/app.js']);
        $this->assertEquals(
            '<link rel="modulepreload" href="preload/fixtures/vendor.222bbb.js">' . "\n" .
            '<link rel="modulepreload" href="preload/fixtures/chunk-vue.333ccc.js">' . "\n" .
            '<link rel="modulepreload" href="preload/fixtures/chunk-utils.444ddd.js">' . "\n" .
            '<link rel="stylesheet" href="preload/fixtures/app.111aaa.css">',
            $tag
        );
    }
}
