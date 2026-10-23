<?php

declare(strict_types=1);

namespace Tests\Application;

use Omega\Application\ApplicationManifest;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Tests\TestCase;

use function chmod;
use function dirname;
use function file_exists;
use function file_put_contents;
use function glob;
use function is_dir;
use function json_encode;
use function mkdir;
use function restore_error_handler;
use function rmdir;
use function set_error_handler;
use function str_contains;
use function unlink;
use function var_export;
use function Omega\Application\slash;

#[CoversClass(ApplicationManifest::class)]
final class ApplicationManifestTest extends TestCase
{
    private string $basePath;

    private string $applicationCachePath;

    private string $applicationManifest;

    protected function setUp(): void
    {
        $this->basePath             = __DIR__ . '/fixtures/application-read/';
        $this->applicationCachePath = __DIR__ . '/fixtures/application-write/bootstrap/cache/';
        $this->applicationManifest  = __DIR__ . '/fixtures/application-write/bootstrap/cache/packages.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->applicationManifest)) {
            @unlink($this->applicationManifest);
        }
    }

    public function testCanBuild(): void
    {
        $applicationManifest = new ApplicationManifest($this->basePath, $this->applicationCachePath, '/package/');
        $applicationManifest->build();

        $this->assertTrue(file_exists($this->applicationManifest));
    }

    public function testCanGetApplicationManifest(): void
    {
        $applicationManifest = new ApplicationManifest($this->basePath, $this->applicationCachePath, '/package/');

        $manifest1 = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest);
        $manifest2 = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest);

        $expected = [
            'packages/package1' => [
                'providers' => [
                    'Package//Package1//ServiceProvider::class',
                ],
            ],
            'packages/package2' => [
                'providers' => [
                    'Package//Package2//ServiceProvider::class',
                    'Package//Package2//ServiceProvider2::class',
                ],
            ],
        ];

        $this->assertEquals($expected, $manifest1);
        $this->assertEquals($expected, $manifest2);
        $this->assertSame($manifest2, $manifest1);
    }

    public function testCanGetConfig(): void
    {
        $package_manifest = new ApplicationManifest(
            $this->basePath,
            $this->applicationCachePath,
            slash(path: '/package/')
        );
        $config = (fn () => $this->{'config'}('providers'))->call($package_manifest);

        $this->assertEquals([
            'Package//Package1//ServiceProvider::class',
            'Package//Package2//ServiceProvider::class',
            'Package//Package2//ServiceProvider2::class',
        ], $config);
    }

    public function testCanGetProviders(): void
    {
        $package_manifest = new ApplicationManifest(
            $this->basePath,
            $this->applicationCachePath,
            slash(path: '/package/')
        );

        $config = $package_manifest->providers();

        $this->assertEquals([
            'Package//Package1//ServiceProvider::class',
            'Package//Package2//ServiceProvider::class',
            'Package//Package2//ServiceProvider2::class',
        ], $config);
    }

    public function testFiltersOutIncompletePackagesStringEntriesAndEmptyValues(): void
    {
        file_put_contents(
            $this->applicationCachePath . 'packages.php',
            '<?php return ' . var_export([
                'pkg_not_array' => 'plain-string',
                'pkg_no_key'    => ['name' => 'x'],
                'pkg_string'    => ['providers' => 'SingleProvider'],
                'pkg_array'     => ['providers' => ['A', '', 'B']],
            ], true) . ';'
        );

        $manifest = new ApplicationManifest($this->basePath, $this->applicationCachePath);

        $this->assertSame(['SingleProvider', 'A', 'B'], $manifest->providers());
    }

    public function testTreatsNonArrayCachedManifestAsEmpty(): void
    {
        file_put_contents(
            $this->applicationCachePath . 'packages.php',
            "<?php return 'not-an-array';"
        );

        $manifest = new ApplicationManifest($this->basePath, $this->applicationCachePath);

        $this->assertSame([], $manifest->providers());
    }

    public function testCollectsConfigValuesAcrossVariedPackageShapes(): void
    {
        file_put_contents(
            $this->applicationCachePath . 'packages.php',
            '<?php return ' . var_export([
                'pkg_object' => (object) ['providers' => ['Kept']],
                'pkg_list'   => ['providers' => ['Keep', 5, '', 'Yes']],
                'pkg_int'    => ['providers' => 123],
                'pkg_empty'  => ['providers' => ''],
            ], true) . ';'
        );

        $manifest = new ApplicationManifest($this->basePath, $this->applicationCachePath);

        $this->assertSame(['Keep', 'Yes'], $manifest->providers());
    }

    public function testUsesCustomVendorPath(): void
    {
        $customPath = '/custom/vendor/';
        $manifest   = new ApplicationManifest('/base', '/cache', $customPath);

        $reflection = new ReflectionProperty(ApplicationManifest::class, 'vendorPath');

        $this->assertSame(slash($customPath), $reflection->getValue($manifest));
    }

    public function testUsesDefaultVendorPath(): void
    {
        $manifest = new ApplicationManifest('/base', '/cache');

        $reflection = new ReflectionProperty(ApplicationManifest::class, 'vendorPath');

        $this->assertSame(slash('/vendor/composer/'), $reflection->getValue($manifest));
    }

    public function testBuildsWhenInstalledJsonIsNotAPackagesArray(): void
    {
        $tempCachePath = __DIR__ . '/fixtures/application-write/bootstrap/cache-malformed/';
        $tempBase      = __DIR__ . '/fixtures/application-write/malformed-base/';

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }
        if (!is_dir($tempBase . '/package/composer/')) {
            mkdir($tempBase . '/package/composer/', 0777, true);
        }
        file_put_contents($tempBase . '/package/composer/installed.json', 'not-valid-json');

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $applicationManifest->build();

        $this->assertTrue(file_exists($tempCachePath . 'packages.php'));

        @unlink($tempCachePath . 'packages.php');
        @unlink($tempBase . '/package/composer/installed.json');
        @rmdir($tempBase . '/package/composer');
        @rmdir($tempBase . '/package');
        @rmdir($tempBase);
        @rmdir($tempCachePath);
    }

    public function testBuildsWhenInstalledJsonDecodesWithoutPackagesArray(): void
    {
        $tempCachePath = __DIR__ . '/fixtures/application-write/bootstrap/cache-malformed-2/';
        $tempBase      = __DIR__ . '/fixtures/application-write/malformed-base-2/';

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }
        if (!is_dir($tempBase . '/package/composer/')) {
            mkdir($tempBase . '/package/composer/', 0777, true);
        }
        file_put_contents($tempBase . '/package/composer/installed.json', json_encode(['foo' => 'bar']));

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $applicationManifest->build();

        $this->assertTrue(file_exists($tempCachePath . 'packages.php'));

        @unlink($tempCachePath . 'packages.php');
        @unlink($tempBase . '/package/composer/installed.json');
        @rmdir($tempBase . '/package/composer');
        @rmdir($tempBase . '/package');
        @rmdir($tempBase);
        @rmdir($tempCachePath);
    }

    public function testBuildsWhileFilteringOutPackagesFailingEachValidationGuard(): void
    {
        $tempCachePath = __DIR__ . '/fixtures/application-write/bootstrap/cache-filters/';
        $tempBase      = __DIR__ . '/fixtures/application-write/filters-base/';

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }
        if (!is_dir($tempBase . '/package/composer/')) {
            mkdir($tempBase . '/package/composer/', 0777, true);
        }

        file_put_contents(
            $tempBase . '/package/composer/installed.json',
            json_encode(['packages' => [
                'not-an-array',
                ['version' => '1.0'],
                ['name' => 123],
                ['name' => 'ok', 'version' => '1.0'],
                ['name' => 'ok2', 'extra' => 'not-array'],
                ['name' => 'ok3', 'extra' => ['a' => 1]],
                ['name' => 'valid', 'extra' => ['omega-mvc' => ['providers' => ['X']]]],
            ]])
        );

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $applicationManifest->build();

        $manifest = require $tempCachePath . 'packages.php';

        $this->assertSame(['valid' => ['providers' => ['X']]], $manifest);
        $this->assertTrue(file_exists($tempCachePath . 'packages.php'));

        @unlink($tempCachePath . 'packages.php');
        @unlink($tempBase . '/package/composer/installed.json');
        @rmdir($tempBase . '/package/composer');
        @rmdir($tempBase . '/package');
        @rmdir($tempBase);
        @rmdir($tempCachePath);
    }

    public function testBuildsWhenDecodedPackagesKeyIsNotArray(): void
    {
        $tempCachePath = __DIR__ . '/fixtures/application-write/bootstrap/cache-not-array/';
        $tempBase      = __DIR__ . '/fixtures/application-write/not-array-base/';

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }
        if (!is_dir($tempBase . '/package/composer/')) {
            mkdir($tempBase . '/package/composer/', 0777, true);
        }
        file_put_contents(
            $tempBase . '/package/composer/installed.json',
            json_encode(['packages' => 'not-an-array'])
        );

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $applicationManifest->build();

        $this->assertTrue(file_exists($tempCachePath . 'packages.php'));

        @unlink($tempCachePath . 'packages.php');
        @unlink($tempBase . '/package/composer/installed.json');
        @rmdir($tempBase . '/package/composer');
        @rmdir($tempBase . '/package');
        @rmdir($tempBase);
        @rmdir($tempCachePath);
    }

    public function testBuildsWhenInstalledJsonFileCannotBeRead(): void
    {
        $tempCachePath = __DIR__ . '/fixtures/application-write/bootstrap/cache-unreadable/';
        $tempBase      = __DIR__ . '/fixtures/application-write/unreadable-base/';

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }
        if (!is_dir($tempBase . '/package/composer/')) {
            mkdir($tempBase . '/package/composer/', 0777, true);
        }
        file_put_contents($tempBase . '/package/composer/installed.json', '{"packages":[]}');
        chmod($tempBase . '/package/composer/installed.json', 0000);

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'file_get_contents');
        });

        try {
            $applicationManifest->build();
        } finally {
            restore_error_handler();
        }

        $this->assertTrue(file_exists($tempCachePath . 'packages.php'));

        @chmod($tempBase . '/package/composer/installed.json', 0644);
        @unlink($tempBase . '/package/composer/installed.json');
        @rmdir($tempBase . '/package/composer');
        @rmdir($tempBase . '/package');
        @rmdir($tempBase);
        @unlink($tempCachePath . 'packages.php');
        @rmdir($tempCachePath);
    }

    public function testGetsEmptyApplicationManifestWhenCacheFileIsMissing(): void
    {
        $tempCachePath = __DIR__ . '/fixtures/application-write/bootstrap/cache-missing/';

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }

        $applicationManifest = new ApplicationManifest($this->basePath, $tempCachePath);

        $manifest = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest);

        $this->assertTrue(file_exists($tempCachePath . 'packages.php'));
        @unlink($tempCachePath . 'packages.php');
    }

    public function testGetsApplicationManifestWhenCacheFileExists(): void
    {
        if (!is_dir($this->applicationCachePath)) {
            mkdir($this->applicationCachePath, 0777, true);
        }
        file_put_contents($this->applicationCachePath . 'packages.php', "<?php return ['test' => 'data'];");

        $applicationManifest = new ApplicationManifest($this->basePath, $this->applicationCachePath);

        $ref = new ReflectionProperty(ApplicationManifest::class, 'applicationManifest');
        $ref->setValue($applicationManifest, null);

        $manifest = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest);

        $this->assertEquals(['test' => 'data'], $manifest);
    }

    public function testCollectsOnlyValidExternalPackagesInFindExternalProvider(): void
    {
        $tempBase      = __DIR__ . '/fixtures/application-write/external-valid/';
        $tempCachePath = __DIR__ . '/fixtures/application-write/external-valid-cache/';

        self::writeExternalComposer(
            'ext/valid',
            ['omega-mvc' => ['providers' => ['External::class']]],
            $tempBase,
            $tempCachePath
        );

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame(['ext/valid' => ['providers' => ['External::class']]], $manifest);

        self::cleanExternalTree($tempBase, $tempCachePath);
    }

    public function testContinuesPastInvalidPackagesAndCollectsValidOnesInFindExternalProvider(): void
    {
        $tempBase      = __DIR__ . '/fixtures/application-write/external-mixed/';
        $tempCachePath = __DIR__ . '/fixtures/application-write/external-mixed-cache/';

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }

        $invalid = $tempBase . '/vendor/omega-mvc/invalid/composer.json';
        mkdir(dirname($invalid), 0777, true);
        file_put_contents($invalid, 'not-valid-json');

        self::writeExternalComposer(
            'ext/mixed',
            ['omega-mvc' => ['providers' => ['Mixed::class']]],
            $tempBase,
            $tempCachePath
        );

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame(['ext/mixed' => ['providers' => ['Mixed::class']]], $manifest);

        self::cleanExternalTree($tempBase, $tempCachePath);
    }

    public function testIgnoresComposerJsonThatCannotBeReadInFindExternalProvider(): void
    {
        $tempBase      = __DIR__ . '/fixtures/application-write/external-unreadable/';
        $tempCachePath = __DIR__ . '/fixtures/application-write/external-unreadable-cache/';

        $file = $tempBase . '/vendor/omega-mvc/unreadable/composer.json';

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }
        mkdir(dirname($file), 0777, true);
        file_put_contents($file, '{"name":"ext/unreadable","extra":{"omega-mvc":{"providers":["X"]}}}');
        chmod($file, 0000);

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'file_get_contents');
        });

        try {
            $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
            $manifest            = $applicationManifest->build();
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $manifest);

        @chmod($file, 0644);
        self::cleanExternalTree($tempBase, $tempCachePath);
    }

    public function testIgnoresInvalidJsonComposerFilesInFindExternalProvider(): void
    {
        $tempBase      = __DIR__ . '/fixtures/application-write/external-invalid-json/';
        $tempCachePath = __DIR__ . '/fixtures/application-write/external-invalid-json-cache/';

        $file = $tempBase . '/vendor/omega-mvc/invalid/composer.json';

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }
        mkdir(dirname($file), 0777, true);
        file_put_contents($file, 'not-valid-json');

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame([], $manifest);

        self::cleanExternalTree($tempBase, $tempCachePath);
    }

    public function testIgnoresPackagesWithoutStringNameInFindExternalProvider(): void
    {
        $tempBase      = __DIR__ . '/fixtures/application-write/external-no-name/';
        $tempCachePath = __DIR__ . '/fixtures/application-write/external-no-name-cache/';

        self::writeExternalComposer(
            null,
            ['omega-mvc' => ['providers' => ['X']]],
            $tempBase,
            $tempCachePath,
            'noname'
        );

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame([], $manifest);

        self::cleanExternalTree($tempBase, $tempCachePath);
    }

    public function testIgnoresPackagesWithoutExtraArrayInFindExternalProvider(): void
    {
        $tempBase      = __DIR__ . '/fixtures/application-write/external-no-extra/';
        $tempCachePath = __DIR__ . '/fixtures/application-write/external-no-extra-cache/';

        self::writeExternalComposer('ext/noextra', null, $tempBase, $tempCachePath);

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame([], $manifest);

        self::cleanExternalTree($tempBase, $tempCachePath);
    }

    public function testIgnoresPackagesLackingOmegaMvcExtraKeyInFindExternalProvider(): void
    {
        $tempBase      = __DIR__ . '/fixtures/application-write/external-no-omega/';
        $tempCachePath = __DIR__ . '/fixtures/application-write/external-no-omega-cache/';

        self::writeExternalComposer('ext/noomega', ['other' => 1], $tempBase, $tempCachePath);

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame([], $manifest);

        self::cleanExternalTree($tempBase, $tempCachePath);
    }

    private static function writeExternalComposer(
        ?string $name,
        mixed $extra,
        string $tempBase,
        string $tempCachePath,
        string $dir = 'pkg'
    ): void {
        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }

        $file = $tempBase . '/vendor/omega-mvc/' . $dir . '/composer.json';
        mkdir(dirname($file), 0777, true);

        $composer = [];
        if ($name !== null) {
            $composer['name'] = $name;
        }
        if ($extra !== null) {
            $composer['extra'] = $extra;
        }

        file_put_contents($file, json_encode($composer));
    }

    private static function cleanExternalTree(string $tempBase, string $tempCachePath): void
    {
        $vendor = $tempBase . '/vendor';

        if (is_dir($vendor)) {
            foreach (glob($vendor . '/omega-mvc/*') ?: [] as $pkg) {
                foreach (glob($pkg . '/*') ?: [] as $f) {
                    @unlink($f);
                }
                @rmdir($pkg);
            }
            @rmdir($vendor . '/omega-mvc');
            @rmdir($vendor);
        }

        @rmdir($tempBase);
        @unlink($tempCachePath . 'packages.php');
        @rmdir($tempCachePath);
    }
}
