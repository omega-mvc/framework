<?php

declare(strict_types=1);

namespace Tests\View;

use Omega\View\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

use function file_put_contents;
use function mkdir;
use function unlink;

#[CoversClass(Vite::class)]
final class ViteTagTest extends TestCase
{
    protected function tearDown(): void
    {
        Vite::flush();

        parent::tearDown();
    }

    public function testEscapeUrl(): void
    {
        $vite   = new Vite(__DIR__, '');
        $escape = (fn (string $url) => $this->{'escapeUrl'}($url))->call($vite, 'foo"bar');
        $this->assertEquals('foo&quot;bar', $escape);
        $escape2 = (fn (string $url) => $this->{'escapeUrl'}($url))->call($vite, 'https://example.com/path');
        $this->assertEquals('https://example.com/path', $escape2);
    }

    public function testIsCssFile(): void
    {
        $vite  = new Vite(__DIR__, '');
        $isCss = (fn (string $file) => $this->{'isCssFile'}($file))->call($vite, 'foo.css');
        $this->assertTrue($isCss);
        $isCss2 = (fn (string $file) => $this->{'isCssFile'}($file))->call($vite, 'bar.scss');
        $this->assertTrue($isCss2);
        $isCss3 = (fn (string $file) => $this->{'isCssFile'}($file))->call($vite, 'baz.js');
        $this->assertFalse($isCss3);
    }

    public function testBuildAttributeString(): void
    {
        $vite = new Vite(__DIR__, '');

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
    }

    public function testCreateStyleTag(): void
    {
        $vite = new Vite(__DIR__, '');

        $createStyleTag = (fn () => $this->{'createStyleTag'}('foo.css'))->call($vite);
        $this->assertEquals('<link rel="stylesheet" href="foo.css">', $createStyleTag);
    }

    public function testCreateScriptTag(): void
    {
        $vite = new Vite(__DIR__, '');

        $createScriptTag = (fn () => $this->{'createScriptTag'}('foo.js'))->call($vite);
        $this->assertEquals('<script type="module" src="foo.js"></script>', $createScriptTag);
    }

    public function testCreateTagWithAttributes(): void
    {
        $vite = new Vite(__DIR__, '');

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
    }

    public function testCreatePreloadTag(): void
    {
        $vite = new Vite(__DIR__, '');

        $createPreloadTag = (fn () => $this->{'createPreloadTag'}('foo.css'))->call($vite);
        $this->assertEquals('<link rel="modulepreload" href="foo.css">', $createPreloadTag);
    }

    public function testGetTags(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

        $tag = $vite->getTags(['resources/js/app.js', 'resources/css/app.css']);
        $this->assertEquals(
            '<link rel="stylesheet" href="build/fixtures/app-4ed993c7.css">' . "\n" .
            '<script type="module" src="build/fixtures/app-0d91dc04.js"></script>',
            $tag
        );
    }

    public function testGetTagsAttributes(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

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

    public function testGetTagsAttributesWithException(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

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

    public function testGetPreloadTags(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'preload/');

        $tag = $vite->getPreloadTags(['resources/js/app.js']);
        $this->assertEquals(
            '<link rel="modulepreload" href="preload/fixtures/vendor.222bbb.js">' . "\n" .
            '<link rel="modulepreload" href="preload/fixtures/chunk-vue.333ccc.js">' . "\n" .
            '<link rel="modulepreload" href="preload/fixtures/chunk-utils.444ddd.js">' . "\n" .
            '<link rel="stylesheet" href="preload/fixtures/app.111aaa.css">',
            $tag
        );
    }

    public function testCanRenderHeadHtmlTag(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

        $headTag = $vite(
            'resources/css/app.css',
            'resources/js/app.js',
        );

        $this->assertEquals(
            '<link rel="stylesheet" href="build/fixtures/app-4ed993c7.css">' . "\n" .
            '<script type="module" src="build/fixtures/app-0d91dc04.js"></script>',
            $headTag
        );
    }

    public function testCanRenderHeadHtmlTagInHrmMode(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/hot/public', 'build/');

        $headTags = $vite(
            'resources/css/app.css',
            'resources/js/app.js'
        );

        $this->assertEquals(
            '<script type="module" src="http://[::1]:5173/@vite/client"></script>' . "\n" .
            '<script type="module" src="http://[::1]:5173/resources/css/app.css"></script>' . "\n" .
            '<script type="module" src="http://[::1]:5173/resources/js/app.js"></script>',
            $headTags
        );
    }

    public function testCanRenderHeadHtmlTagWithPreload(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'preload/');

        $headTag = $vite('resources/js/app.js');

        $this->assertEquals(
            '<link rel="modulepreload" href="preload/fixtures/vendor.222bbb.js">' . "\n" .
            '<link rel="modulepreload" href="preload/fixtures/chunk-vue.333ccc.js">' . "\n" .
            '<link rel="modulepreload" href="preload/fixtures/chunk-utils.444ddd.js">' . "\n" .
            '<link rel="stylesheet" href="preload/fixtures/app.111aaa.css">' . "\n" .
            '<script type="module" src="preload/fixtures/app.111aaa.js"></script>',
            $headTag
        );
    }

    public function testGetCustomTagsWithHmr(): void
    {
        $tmpDir = __DIR__ . '/fixtures/application-write/manifest1/public';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0777, true);
        }

        file_put_contents($tmpDir . '/hot', 'http://localhost:5173');

        $vite = new Vite($tmpDir, 'build');

        $entryPoints = [
            'app.js'   => ['async' => true],
            'main.css' => [],
        ];

        $tags = $vite->getCustomTags($entryPoints);

        $this->assertStringContainsString('@vite/client', $tags);

        $this->assertStringContainsString(
            '<script type="module" async src="http://localhost:5173/app.js"></script>',
            $tags
        );

        $this->assertStringContainsString(
            'http://localhost:5173/main.css',
            $tags
        );

        unlink($tmpDir . '/hot');
    }

    public function testBuildAttributeStringWithEmptyAttributesReturnsEmptyString(): void
    {
        $vite = new Vite('/public', '/build');

        $reflection = new ReflectionClass($vite);
        $method     = $reflection->getMethod('buildAttributeString');

        $result = $method->invoke($vite, []);

        $this->assertSame('', $result);
    }

    public function testCreateScriptTagWithTypeAlreadySet(): void
    {
        $vite = new Vite('/public', '/build');

        $attributes = ['type' => 'text/javascript', 'async' => true];

        $result = $this->invokeCreateScriptTag($vite, 'app.js', $attributes);

        $this->assertStringContainsString('type="text/javascript"', $result);
        $this->assertStringContainsString('src="app.js"', $result);
        $this->assertStringContainsString('async', $result);

        $this->assertStringStartsWith('<script ', $result);
        $this->assertStringEndsWith('</script>', $result);
    }

    /**
     * @param array<int|string, bool|int|string|null>|null $attributes
     */
    private function invokeCreateScriptTag(Vite $vite, string $url, ?array $attributes = null): string
    {
        $method = new ReflectionMethod(Vite::class, 'createScriptTag');

        /** @var string $result */
        $result = $method->invoke($vite, $url, $attributes);

        return $result;
    }
}
