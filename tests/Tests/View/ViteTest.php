<?php

declare(strict_types=1);

namespace Tests\View;

use Exception;
use Omega\View\Vite;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function chmod;
use function dirname;
use function escapeshellarg;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function mkdir;
use function rmdir;
use function shell_exec;
use function sprintf;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;
use function var_export;

#[CoversClass(Vite::class)]
final class ViteTest extends TestCase
{
    protected function tearDown(): void
    {
        Vite::flush();

        parent::tearDown();
    }

    public function testCanGetFileResourceName(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

        $file = $asset->get('resources/css/app.css');

        $this->assertEquals('build/fixtures/app-4ed993c7.css', $file);
    }

    public function testCanGetFileResourceNames(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

        $files = $asset->gets([
            'resources/css/app.css',
            'resources/js/app.js',
        ]);

        $this->assertEquals([
            'resources/css/app.css' => 'build/fixtures/app-4ed993c7.css',
            'resources/js/app.js'   => 'build/fixtures/app-0d91dc04.js',
        ], $files);
    }

    public function testCanCheckRunningHrmExist(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/hot/public', 'build/');

        $this->assertTrue($asset->isRunningHRM());
    }

    public function testCanCheckRunningHrmDoesExist(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

        $this->assertFalse($asset->isRunningHRM());
    }

    public function testCanGetHotFileResourceName(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/hot/public', 'build/');

        $file = $asset->get('resources/css/app.css');

        $this->assertEquals('http://[::1]:5173/resources/css/app.css', $file);
    }

    public function testCanGetHotFileResourceNames(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/hot/public', 'build/');

        $files = $asset->gets([
            'resources/css/app.css',
            'resources/js/app.js',
        ]);

        $this->assertEquals([
            'resources/css/app.css' => 'http://[::1]:5173/resources/css/app.css',
            'resources/js/app.js'   => 'http://[::1]:5173/resources/js/app.js',
        ], $files);
    }

    public function testCanUseCache(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');
        $asset->get('resources/css/app.css');

        $this->assertCount(1, Vite::$cache);
    }

    public function testCanGetHotUrl(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/hot/public', 'build/');

        $this->assertEquals('http://[::1]:5173/', $asset->getHmrUrl());
    }

    public function testCanGetHmrScript(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/hot/public', 'build/');

        $this->assertEquals(
            '<script type="module" src="http://[::1]:5173/@vite/client"></script>',
            $asset->getHmrScript()
        );
    }

    public function testInvokeReturnsEmptyStringWhenNoEntryPointsAreProvided(): void
    {
        $vite = new Vite(__DIR__, 'build');

        $result = $vite();

        $this->assertSame('', $result);
    }

    public function testUsesCustomManifestName(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

        $vite->manifestName('custom-manifest.json');

        $this->assertStringEndsWith('custom-manifest.json', $vite->manifest());
    }

    public function testThrowsExceptionIfManifestFileNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIsOrContains('Manifest file not found');

        $vite = new Vite(__DIR__ . '/fixtures', 'build');

        // Nome volutamente inesistente
        $vite->manifestName('does-not-exist.json');

        $vite->manifest();
    }

    public function testManifestThrowsExceptionIfFileDoesNotExist(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build');
        $vite->manifestName('nonexistent.json');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Manifest file not found/');

        $vite->manifest();
    }

    public function testLoaderThrowsExceptionIfFileCannotBeRead(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build');
        $vite->manifestName('unreadable.json');

        $filePath = __DIR__ . '/fixtures/support/manifest/public/build/unreadable.json';
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
    }

    public function testLoaderThrowsExceptionOnInvalidJson(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build');
        $vite->manifestName('invalid.json');

        $filePath = __DIR__ . '/fixtures/support/manifest/public/build/invalid.json';
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, '{invalid json');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Manifest JSON decode error/');

        $vite->loader();
    }

    public function testCanGetTheManifestPathForASpecificResource(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

        $path = $asset->getManifest('resources/js/app.js');

        $this->assertEquals('build/fixtures/app-0d91dc04.js', $path);
    }

    public function testThrowsExceptionIfResourceNotFoundInManifest(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageIsOrContains('Resource file not found non-existent-file.js');

        $asset->getManifest('non-existent-file.js');
    }

    public function testReturnsCachedHotUrlOnSecondCall(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/hot/public', 'build/');

        $url1 = $asset->getHmrUrl();
        $url2 = $asset->getHmrUrl();

        $this->assertEquals($url1, $url2);
        $this->assertNotNull(Vite::$hot);
    }

    public function testThrowsExceptionIfHotFileIsUnreadable(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/application-write/public/', 'build/');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageIsOrContains('Failed to read hot file');

        $asset->getHmrUrl();
    }

    public function testHandlesHotFileWithTrailingSlash(): void
    {
        $tempDir = sys_get_temp_dir() . '/vite_test_' . uniqid();
        mkdir($tempDir);
        file_put_contents($tempDir . '/hot', "http://localhost:5173/\n");

        $asset = new Vite($tempDir, 'build/');
        $url   = $asset->getHmrUrl();

        $this->assertEquals('http://localhost:5173/', $url);

        unlink($tempDir . '/hot');
        rmdir($tempDir);
    }

    public function testUpdatesAndReturnsCacheTime(): void
    {
        $manifestDir = __DIR__ . '/fixtures/application-write/manifest/public/build';

        if (!is_dir($manifestDir)) {
            mkdir($manifestDir, 0777, true);
        }

        $manifestPath = "{$manifestDir}/manifest.json";
        file_put_contents($manifestPath, json_encode([
            'resources/js/app.js' => ['file' => 'app.js'],
        ]));

        $expectedTime = time() - 3600;
        touch($manifestPath, $expectedTime);

        $vite = new Vite(__DIR__ . '/fixtures/application-write/manifest/public', 'build');

        $this->assertEquals(0, $vite->cacheTime());

        $vite->loader();

        $this->assertEquals($expectedTime, $vite->cacheTime());
        $this->assertEquals($expectedTime, $vite->manifestTime());

        unlink($manifestPath);
        rmdir($manifestDir);
    }

    public function testGetPreloadTagsReturnsEmptyStringWhenHmrIsRunning(): void
    {
        $publicPath = __DIR__ . '/fixtures/application-write/manifest/public';
        if (!is_dir($publicPath)) {
            @mkdir($publicPath, 0777, true);
        }

        file_put_contents("{$publicPath}/hot", 'http://localhost:3000');

        $vite = new Vite($publicPath, 'build');

        $result = $vite->getPreloadTags(['main.js']);

        $this->assertSame('', $result);

        unlink("{$publicPath}/hot");
        rmdir($publicPath);
    }

    public function testCanGetHotFileResourceNamesUsingCachedHotUrl(): void
    {
        $asset = new Vite(__DIR__ . '/fixtures/support/hot/public', 'build/');

        $asset->getHmrUrl();

        $files = $asset->gets([
            'resources/css/app.css',
        ]);

        $this->assertEquals([
            'resources/css/app.css' => 'http://[::1]:5173/resources/css/app.css',
        ], $files);
    }

    public function testGetsSkipsTheEntriesThatAreMissingFromTheManifest(): void
    {
        $public = __DIR__ . '/fixtures/application-write/missing-entry/public';
        $build  = $public . '/build';

        if (!is_dir($build)) {
            mkdir($build, 0777, true);
        }

        file_put_contents("{$build}/manifest.json", json_encode([
            'resources/css/app.css' => ['file' => 'fixtures/app-4ed993c7.css'],
        ]));

        $vite = new Vite($public, 'build');

        try {
            $files = $vite->gets(['resources/js/app.js']);

            $this->assertSame([], $files);
        } finally {
            unlink("{$build}/manifest.json");
            rmdir($build);
            rmdir($public);
        }
    }

    public function testGetsSkipsTheManifestEntriesWhoseFileIsNotAString(): void
    {
        $public = __DIR__ . '/fixtures/application-write/non-string-file/public';
        $build  = $public . '/build';

        if (!is_dir($build)) {
            mkdir($build, 0777, true);
        }

        file_put_contents("{$build}/manifest.json", json_encode([
            'resources/js/app.js' => ['file' => 123],
        ]));

        $vite = new Vite($public, 'build');

        try {
            $files = $vite->gets(['resources/js/app.js']);

            $this->assertSame([], $files);
        } finally {
            unlink("{$build}/manifest.json");
            rmdir($build);
            rmdir($public);
        }
    }

    public function testThrowsExceptionWhenTheManifestEntryFileIsNotAString(): void
    {
        $public = __DIR__ . '/fixtures/application-write/non-string-file/public';
        $build  = $public . '/build';

        if (!is_dir($build)) {
            mkdir($build, 0777, true);
        }

        file_put_contents("{$build}/manifest.json", json_encode([
            'main.js' => ['file' => 123],
        ]));

        $vite = new Vite($public, 'build');

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessageIsOrContains('Invalid manifest entry for resource file main.js');

            $vite->get('main.js');
        } finally {
            if (file_exists("{$build}/manifest.json")) {
                unlink("{$build}/manifest.json");
            }
            rmdir($build);
            rmdir($public);
        }
    }

    public function testGetsReturnsAnEmptyArrayWhenNoResourceNamesAreProvided(): void
    {
        $vite = new Vite(__DIR__ . '/fixtures/support/manifest/public', 'build/');

        $this->assertSame([], $vite->gets([]));
    }

    public function testGetsKeepsTheEntriesWhoseFileKeyIsMissingUsingAnEmptyRelativePath(): void
    {
        $public = __DIR__ . '/fixtures/application-write/no-file-key/public';
        $build  = $public . '/build';

        if (!is_dir($build)) {
            mkdir($build, 0777, true);
        }

        file_put_contents("{$build}/manifest.json", json_encode([
            'resources/js/app.js' => [],
        ]));

        $vite = new Vite($public, 'build');

        try {
            $files = $vite->gets(['resources/js/app.js']);

            $this->assertEquals(['resources/js/app.js' => 'build'], $files);
        } finally {
            unlink("{$build}/manifest.json");
            rmdir($build);
            rmdir($public);
        }
    }

    public function testCollectImportsSkipsImportsMissingFromTheManifest(): void
    {
        $public = __DIR__ . '/fixtures/application-write/dangling-import/public';
        $build  = $public . '/build';

        if (!is_dir($build)) {
            mkdir($build, 0777, true);
        }

        file_put_contents("{$build}/manifest.json", json_encode([
            'resources/js/app.js' => [
                'file'    => 'fixtures/app.js',
                'imports' => ['fixtures/missing.js'],
            ],
        ]));

        $vite = new Vite($public, 'build');

        try {
            $preload = $vite->getManifestImports(['resources/js/app.js']);

            $this->assertEquals([
                'imports' => ['fixtures/missing.js'],
                'css'     => [],
            ], $preload);
        } finally {
            unlink("{$build}/manifest.json");
            rmdir($build);
            rmdir($public);
        }
    }

    public function testLoaderThrowsExceptionWhenManifestPathCannotBeOpened(): void
    {
        $public = sys_get_temp_dir() . '/vite_manifest_socket_' . uniqid();
        $build  = $public . '/build';

        mkdir($build, 0777, true);
        $this->createSocketFixture($build . '/manifest.json');

        $this->assertTrue(file_exists($build . '/manifest.json'));

        $vite = new Vite($public, 'build');

        set_error_handler(static fn (): bool => true);
        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessageIsOrContains('Failed to read manifest file');

            $vite->loader();
        } finally {
            restore_error_handler();
            @unlink($build . '/manifest.json');
            @rmdir($build);
            @rmdir($public);
        }
    }

    public function testThrowsExceptionWhenHotFileCannotBeOpened(): void
    {
        $public = sys_get_temp_dir() . '/vite_hot_socket_' . uniqid();

        mkdir($public, 0777, true);
        $this->createSocketFixture($public . '/hot');

        $this->assertTrue(file_exists($public . '/hot'));

        $vite = new Vite($public, 'build/');

        set_error_handler(static fn (): bool => true);
        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessageIsOrContains('Failed to read hot file');

            $vite->getHmrUrl();
        } finally {
            restore_error_handler();
            @unlink($public . '/hot');
            @rmdir($public);
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenTheManifestModificationTimeCannotBeRead(): void
    {
        $vite = $this->getMockBuilder(Vite::class)
            ->setConstructorArgs(['/tmp', 'build'])
            ->onlyMethods(['manifest'])
            ->getMock();
        $vite->method('manifest')->willReturn(__DIR__ . '/fixtures/inexistent/manifest.json');

        set_error_handler(static fn (): bool => true);
        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Manifest file modification time cannot be read.');

            $vite->manifestTime();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Create a unix socket file from a short-lived subprocess so that no socket
     * syscall ever happens inside the test process. The socket inode remains
     * after the child exits; reading such an inode fails cleanly (false), which
     * is what the Vite "unable to read" branches rely on.
     */
    private function createSocketFixture(string $path): void
    {
        $code = sprintf(
            'error_reporting(0); if (is_dir(%s)) { rmdir(%s); } else { @unlink(%s); } '
            . 'stream_socket_server(%s, $errno, $errstr);',
            var_export($path, true),
            var_export($path, true),
            var_export($path, true),
            var_export('unix://' . $path, true)
        );

        shell_exec(escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=off -r ' . escapeshellarg($code) . ' 2>/dev/null');
    }
}
