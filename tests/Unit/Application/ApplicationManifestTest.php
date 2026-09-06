<?php

/**
 * Part of Omega - Tests\Application Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpExpressionResultUnusedInspection */

declare(strict_types=1);

namespace Tests\Application;

use Omega\Application\ApplicationManifest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\FixturesPathTrait;

use function chmod;
use function dirname;
use function file_exists;
use function file_put_contents;
use function glob;
use function is_dir;
use function json_encode;
use function mkdir;
use function rmdir;
use function restore_error_handler;
use function set_error_handler;
use function str_contains;
use function unlink;
use function var_export;
use function Omega\Application\slash;

/**
 * Tests the ApplicationManifest support class.
 *
 * This test suite verifies the behavior of the ApplicationManifest component,
 * including building the package manifest file, reading package metadata
 * from installed packages, and resolving configuration values such as
 * service providers.
 *
 * The tests rely on a read-only fixture directory for input data and a
 * write-only fixture directory for generated cache files, ensuring that
 * filesystem side effects are isolated and deterministic.
 *
 * @category  Tests
 * @package   Aplication
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(ApplicationManifest::class)]
#[CoversFunction('Omega\Application\slash')]
class ApplicationManifestTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Base path of the application fixtures used for reading package metadata.
     *
     * This path points to a read-only fixture directory that mimics the
     * application root structure.
     *
     * @var string
     */
    private string $basePath;

    /**
     * Path to the application cache directory used during tests.
     *
     * This directory is used as a write-only location where the package
     * manifest file is generated.
     *
     * @var string
     */
    private string $applicationCachePath;

    /**
     * Full path to the generated package manifest file.
     *
     * This file is created during the build process and removed after each
     * test to avoid state leakage between tests.
     *
     * @var string
     */
    private string $applicationManifest;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath             = $this->setFixturePath('/fixtures/application-read/');
        $this->applicationCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache/');
        $this->applicationManifest  = $this->setFixturePath('/fixtures/application-write/bootstrap/cache/packages.php');
    }

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
        parent::tearDown();

        if (file_exists($this->applicationManifest)) {
            @unlink($this->applicationManifest);
        }
    }

    /**
     * Test it can build.
     *
     * @return void
     */
    public function testItCanBuild(): void
    {
        $applicationManifest = new ApplicationManifest($this->basePath, $this->applicationCachePath, '/package/');
        $applicationManifest->build();

        $this->assertTrue(file_exists($this->applicationManifest));
    }

    /**
     * Test it can get package manifest.
     *
     * @return void
     */
    public function testItCanGetApplicationManifest(): void
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

        $this->assertSame($manifest1, $manifest2);
    }

    /**
     * Test it can get config.
     *
     * @return void
     */
    public function testItCanGetConfig(): void
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

    /**
     * Test it can get providers.
     *
     * @return void
     */
    public function testItCanGetProviders(): void
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

    /**
     * Test it filters out incomplete packages, string entries and empty values.
     *
     * @return void
     */
    public function testItFiltersIncompleteAndStringPackages(): void
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

    /**
     * Test it treats a non-array cached manifest as empty.
     *
     * @return void
     */
    public function testItTreatsNonArrayManifestAsEmpty(): void
    {
        file_put_contents(
            $this->applicationCachePath . 'packages.php',
            "<?php return 'not-an-array';"
        );

        $manifest = new ApplicationManifest($this->basePath, $this->applicationCachePath);

        $this->assertSame([], $manifest->providers());
    }

    /**
     * Test collecting config values across varied package shapes.
     *
     * @return void
     */
    public function testItCollectsVariedConfigValues(): void
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

    /**
     * Test custom vendor path.
     *
     * @return void
     */
    public function testCustomVendorPath(): void
    {
        $customPath = '/custom/vendor/';
        $manifest   = new ApplicationManifest('/base', '/cache', $customPath);

        $reflection = new ReflectionProperty(ApplicationManifest::class, 'vendorPath');
        $reflection->setAccessible(true);

        $this->assertSame(slash($customPath), $reflection->getValue($manifest));
    }

    /**
     * Test default vendor path.
     *
     * @return void
     */
    public function testDefaultVendorPath(): void
    {
        $manifest = new ApplicationManifest('/base', '/cache');

        $reflection = new ReflectionProperty(ApplicationManifest::class, 'vendorPath');
        $reflection->setAccessible(true);

        $this->assertSame(slash('/vendor/composer/'), $reflection->getValue($manifest));
    }

    /**
     * Test building when the installed.json is not a packages array.
     *
     * @return void
     */
    public function testBuildWithMalformedInstalledJson(): void
    {
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-malformed/');
        $tempBase      = $this->setFixturePath('/fixtures/application-write/malformed-base/');

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }
        if (!is_dir($tempBase . '/package/composer/')) {
            mkdir($tempBase . '/package/composer/', 0777, true);
        }
        file_put_contents($tempBase . '/package/composer/installed.json', 'not-valid-json');

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $applicationManifest->build();

        $this->assertFileExists($tempCachePath . 'packages.php');

        @unlink($tempCachePath . 'packages.php');
        @unlink($tempBase . '/package/composer/installed.json');
        @rmdir($tempBase . '/package/composer');
        @rmdir($tempBase . '/package');
        @rmdir($tempBase);
        @rmdir($tempCachePath);
    }

    /**
     * Test building when installed.json decodes without a packages array.
     *
     * @return void
     */
    public function testBuildWithDecodedJsonMissingPackages(): void
    {
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-malformed-2/');
        $tempBase      = $this->setFixturePath('/fixtures/application-write/malformed-base-2/');

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }
        if (!is_dir($tempBase . '/package/composer/')) {
            mkdir($tempBase . '/package/composer/', 0777, true);
        }
        file_put_contents($tempBase . '/package/composer/installed.json', json_encode(['foo' => 'bar']));

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $applicationManifest->build();

        $this->assertFileExists($tempCachePath . 'packages.php');

        @unlink($tempCachePath . 'packages.php');
        @unlink($tempBase . '/package/composer/installed.json');
        @rmdir($tempBase . '/package/composer');
        @rmdir($tempBase . '/package');
        @rmdir($tempBase);
        @rmdir($tempCachePath);
    }

    /**
     * Test building filters out packages failing each validation guard.
     *
     * @return void
     */
    public function testBuildFiltersInvalidPackages(): void
    {
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-filters/');
        $tempBase      = $this->setFixturePath('/fixtures/application-write/filters-base/');

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
        $this->assertFileExists($tempCachePath . 'packages.php');

        @unlink($tempCachePath . 'packages.php');
        @unlink($tempBase . '/package/composer/installed.json');
        @rmdir($tempBase . '/package/composer');
        @rmdir($tempBase . '/package');
        @rmdir($tempBase);
        @rmdir($tempCachePath);
    }

    /**
     * Test building when the decoded packages key is not an array.
     *
     * @return void
     */
    public function testBuildWithJsonPackagesNotAnArray(): void
    {
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-not-array/');
        $tempBase      = $this->setFixturePath('/fixtures/application-write/not-array-base/');

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

        $this->assertFileExists($tempCachePath . 'packages.php');

        @unlink($tempCachePath . 'packages.php');
        @unlink($tempBase . '/package/composer/installed.json');
        @rmdir($tempBase . '/package/composer');
        @rmdir($tempBase . '/package');
        @rmdir($tempBase);
        @rmdir($tempCachePath);
    }

    /**
     * Test building when the installed.json file cannot be read.
     *
     * @return void
     */
    public function testBuildWithUnreadableInstalledJson(): void
    {
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-unreadable/');
        $tempBase      = $this->setFixturePath('/fixtures/application-write/unreadable-base/');

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

        $this->assertFileExists($tempCachePath . 'packages.php');

        @chmod($tempBase . '/package/composer/installed.json', 0644);
        @unlink($tempBase . '/package/composer/installed.json');
        @rmdir($tempBase . '/package/composer');
        @rmdir($tempBase . '/package');
        @rmdir($tempBase);
        @unlink($tempCachePath . 'packages.php');
        @rmdir($tempCachePath);
    }

    /**
     * Test get package manifest file path.
     *
     * @return void
     */
    public function testGetApplicationManifestWhenCacheFileMissing(): void
    {
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-missing/');

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }

        $applicationManifest = new ApplicationManifest($this->basePath, $tempCachePath);

        $manifest = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest);

        $this->assertIsArray($manifest);

        $this->assertFileExists($tempCachePath . 'packages.php');

        @unlink($tempCachePath . 'packages.php');
    }

    /**
     * Test get package manifest when cache file exists.
     *
     * @return void
     */
    public function testGetApplicationManifestWhenCacheFileExists(): void
    {
        if (!is_dir($this->applicationCachePath)) {
            mkdir($this->applicationCachePath, 0777, true);
        }
        file_put_contents($this->applicationCachePath . 'packages.php', "<?php return ['test' => 'data'];");

        $applicationManifest = new ApplicationManifest($this->basePath, $this->applicationCachePath);

        $ref = new ReflectionProperty(ApplicationManifest::class, 'applicationManifest');
        $ref->setAccessible(true);
        $ref->setValue($applicationManifest, null);

        $manifest = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest);

        $this->assertEquals(['test' => 'data'], $manifest);
    }

    /**
     * Test findExternalProvider collects only valid external packages.
     *
     * @return void
     */
    public function testFindExternalProviderCollectsValidPackages(): void
    {
        $tempBase      = $this->setFixturePath('/fixtures/application-write/external-valid/');
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-valid-cache/');

        $this->writeExternalComposer(
            'ext/valid',
            ['omega-mvc' => ['providers' => ['External::class']]],
            $tempBase,
            $tempCachePath
        );

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame(
            ['ext/valid' => ['providers' => ['External::class']]],
            $manifest
        );

        $this->cleanExternalTree($tempBase, $tempCachePath);
    }

    /**
     * Test findExternalProvider continues past invalid packages and collects valid ones.
     *
     * @return void
     */
    public function testFindExternalProviderContinuesPastInvalidPackages(): void
    {
        $tempBase      = $this->setFixturePath('/fixtures/application-write/external-mixed/');
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-mixed-cache/');

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }

        $invalid = $tempBase . '/vendor/omega-mvc/invalid/composer.json';
        mkdir(dirname($invalid), 0777, true);
        file_put_contents($invalid, 'not-valid-json');

        $this->writeExternalComposer(
            'ext/mixed',
            ['omega-mvc' => ['providers' => ['Mixed::class']]],
            $tempBase,
            $tempCachePath
        );

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame(
            ['ext/mixed' => ['providers' => ['Mixed::class']]],
            $manifest
        );

        $this->cleanExternalTree($tempBase, $tempCachePath);
    }

    /**
     * Test findExternalProvider ignores a composer.json that cannot be read.
     *
     * @return void
     */
    public function testFindExternalProviderIgnoresUnreadableComposer(): void
    {
        $tempBase      = $this->setFixturePath('/fixtures/application-write/external-unreadable/');
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-unreadable-cache/');

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
        $this->cleanExternalTree($tempBase, $tempCachePath);
    }

    /**
     * Test findExternalProvider ignores invalid JSON composer files.
     *
     * @return void
     */
    public function testFindExternalProviderIgnoresInvalidJson(): void
    {
        $tempBase      = $this->setFixturePath('/fixtures/application-write/external-invalid-json/');
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-invalid-json-cache/');

        $file = $tempBase . '/vendor/omega-mvc/invalid/composer.json';

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }
        mkdir(dirname($file), 0777, true);
        file_put_contents($file, 'not-valid-json');

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame([], $manifest);

        $this->cleanExternalTree($tempBase, $tempCachePath);
    }

    /**
     * Test findExternalProvider ignores packages without a string name.
     *
     * @return void
     */
    public function testFindExternalProviderIgnoresMissingName(): void
    {
        $tempBase      = $this->setFixturePath('/fixtures/application-write/external-no-name/');
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-no-name-cache/');

        $this->writeExternalComposer(
            null,
            ['omega-mvc' => ['providers' => ['X']]],
            $tempBase,
            $tempCachePath,
            'noname'
        );

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame([], $manifest);

        $this->cleanExternalTree($tempBase, $tempCachePath);
    }

    /**
     * Test findExternalProvider ignores packages without an extra array.
     *
     * @return void
     */
    public function testFindExternalProviderIgnoresMissingExtra(): void
    {
        $tempBase      = $this->setFixturePath('/fixtures/application-write/external-no-extra/');
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-no-extra-cache/');

        $this->writeExternalComposer('ext/noextra', null, $tempBase, $tempCachePath);

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame([], $manifest);

        $this->cleanExternalTree($tempBase, $tempCachePath);
    }

    /**
     * Test findExternalProvider ignores packages lacking the omega-mvc extra key.
     *
     * @return void
     */
    public function testFindExternalProviderIgnoresMissingOmegaMvcKey(): void
    {
        $tempBase      = $this->setFixturePath('/fixtures/application-write/external-no-omega/');
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-no-omega-cache/');

        $this->writeExternalComposer('ext/noomega', ['other' => 1], $tempBase, $tempCachePath);

        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();

        $this->assertSame([], $manifest);

        $this->cleanExternalTree($tempBase, $tempCachePath);
    }

    /**
     * Write a composer.json fixture under the vendor/omega-mvc directory.
     *
     * @param string|null $name          The package name, or null to omit the name key.
     * @param mixed       $extra         The extra data, or null to omit the extra key.
     * @param string      $tempBase      The temporary base path.
     * @param string      $tempCachePath The temporary cache path.
     * @param string      $dir            The subdirectory under vendor/omega-mvc.
     * @return void
     */
    private function writeExternalComposer(
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

    /**
     * Remove a temporary external-provider fixture tree.
     *
     * @param string $tempBase      The temporary base path.
     * @param string $tempCachePath The temporary cache path.
     * @return void
     */
    private function cleanExternalTree(string $tempBase, string $tempCachePath): void
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
