<?php

declare(strict_types=1);

namespace Tests\Support;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Support\Vite;

#[CoversClass(Vite::class)]
final class ViteTest extends TestCase
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
     * Test it can get file resource name.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanGetFileResourceName(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/manifest/public', 'build/');

        $file = $asset->get('resources/css/app.css');

        $this->assertEquals('build/fixtures/app-4ed993c7.css', $file);
    }

    /**
     * Test it can get file resource names.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanGetFileResourceNames(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/manifest/public', 'build/');

        $files = $asset->gets([
            'resources/css/app.css',
            'resources/js/app.js',
        ]);

        $this->assertEquals([
            'resources/css/app.css' => 'build/fixtures/app-4ed993c7.css',
            'resources/js/app.js'   => 'build/fixtures/app-0d91dc04.js',
        ], $files);
    }

    /**
     * Test it can check running hrm exist.
     *
     * @return void
     */
    public function testItCanCheckRunningHRMExist(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/hot/public', 'build/');

        $this->assertTrue($asset->isRunningHRM());
    }

    /**
     * Test it can check running hrm does exist.
     *
     * @return void
     */
    public function testItCanCheckRunningHRMDoestExist(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/manifest/public', 'build/');

        $this->assertFalse($asset->isRunningHRM());
    }

    /**
     * Test it can get hot file resource name.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanGetHotFileResourceName(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/hot/public', 'build/');

        $file = $asset->get('resources/css/app.css');

        $this->assertEquals('http://[::1]:5173/resources/css/app.css', $file);
    }

    /**
     * Test it can get hot file resource names.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanGetHotFileResourceNames(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/hot/public', 'build/');

        $files = $asset->gets([
            'resources/css/app.css',
            'resources/js/app.js',
        ]);

        $this->assertEquals([
            'resources/css/app.css' => 'http://[::1]:5173/resources/css/app.css',
            'resources/js/app.js'   => 'http://[::1]:5173/resources/js/app.js',
        ], $files);
    }

    /**
     * Test it can use cache.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanUseCache(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/manifest/public', 'build/');
        $asset->get('resources/css/app.css');

        $this->assertCount(1, Vite::$cache);
    }

    /**
     * Test it can get hot uri.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanGetHotUrl(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/hot/public', 'build/');

        $this->assertEquals(
            'http://[::1]:5173/',
            $asset->getHmrUrl()
        );
    }

    /**
     * Test it can get hmr script.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanGetHmrScript(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/hot/public', 'build/');

        $this->assertEquals(
            '<script type="module" src="http://[::1]:5173/@vite/client"></script>',
            $asset->getHmrScript()
        );
    }

    /**
     * Test it can render head HTML tag.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanRenderHeadHtmlTag(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/manifest/public', 'build/');

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

    /**
     * Test it can render head HTML tag with preload.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanRenderHeadHtmlTagWithPreload(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/manifest/public', 'preload/');

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

    /**
     * Test it can render head HTML tag in hrm mode.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanRenderHeadHtmlTagInHrmMode(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/hot/public', 'build/');

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
}
