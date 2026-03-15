<?php /** @noinspection PhpExpressionResultUnusedInspection */

/**
 * Part of Omega - Tests\Support Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support;

use Omega\Support\PackageManifest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\FixturesPathTrait;

/**
 * Tests the PackageManifest support class.
 *
 * This test suite verifies the behavior of the PackageManifest component,
 * including building the package manifest file, reading package metadata
 * from installed packages, and resolving configuration values such as
 * service providers.
 *
 * The tests rely on a read-only fixture directory for input data and a
 * write-only fixture directory for generated cache files, ensuring that
 * filesystem side effects are isolated and deterministic.
 *
 * @category  Tests
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(PackageManifest::class)]
class PackageManifestTest extends TestCase
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
    private string $packageManifest;

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
        $this->packageManifest      = $this->setFixturePath('/fixtures/application-write/bootstrap/cache/packages.php');
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

        if (file_exists($this->packageManifest)) {
            @unlink($this->packageManifest);
        }
    }

    /**
     * Test it can build.
     *
     * @return void
     */
    public function testItCanBuild(): void
    {
        $packageManifest = new PackageManifest($this->basePath, $this->applicationCachePath, '/package/');
        $packageManifest->build();

        $this->assertTrue(file_exists($this->packageManifest));
    }

    /**
     * Test it can get package manifest.
     *
     * @return void
     */
    public function testItCanGetPackageManifest(): void
    {
        $packageManifest = new PackageManifest($this->basePath, $this->applicationCachePath, '/package/');

        $manifest1 = (fn () => $this->{'getPackageManifest'}())->call($packageManifest);
        $manifest2 = (fn () => $this->{'getPackageManifest'}())->call($packageManifest);

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
        $package_manifest = new PackageManifest($this->basePath, $this->applicationCachePath, slash(path: '/package/'));
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
        $package_manifest = new PackageManifest($this->basePath, $this->applicationCachePath, slash(path: '/package/'));

        $config = $package_manifest->providers();

        $this->assertEquals([
            'Package//Package1//ServiceProvider::class',
            'Package//Package2//ServiceProvider::class',
            'Package//Package2//ServiceProvider2::class',
        ], $config);
    }

    /**
     * Test custom vendor path.
     *
     * @return void
     */
    public function testCustomVendorPath(): void
    {
        $customPath = '/custom/vendor/';
        $manifest   = new PackageManifest('/base', '/cache', $customPath);

        $reflection = new ReflectionProperty(PackageManifest::class, 'vendorPath');
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
        $manifest = new PackageManifest('/base', '/cache');

        $reflection = new ReflectionProperty(PackageManifest::class, 'vendorPath');
        $reflection->setAccessible(true);

        $this->assertSame(slash('/vendor/composer/'), $reflection->getValue($manifest));
    }

    /**
     * Test get package manifest file path.
     *
     * @return void
     */
    public function testGetPackageManifestWhenCacheFileMissing(): void
    {
        $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-missing/');

        if (!is_dir($tempCachePath)) {
            mkdir($tempCachePath, 0777, true);
        }

        $packageManifest = new PackageManifest($this->basePath, $tempCachePath);

        $manifest = (fn () => $this->{'getPackageManifest'}())->call($packageManifest);

        $this->assertIsArray($manifest);

        $this->assertFileExists($tempCachePath . 'packages.php');

        @unlink($tempCachePath . 'packages.php');
    }

    /**
     * Test get package manifest when cache file exists.
     *
     * @return void
     */
    public function testGetPackageManifestWhenCacheFileExists(): void
    {
        if (!is_dir($this->applicationCachePath)) mkdir($this->applicationCachePath, 0777, true);
        file_put_contents($this->applicationCachePath . 'packages.php', "<?php return ['test' => 'data'];");

        $packageManifest = new PackageManifest($this->basePath, $this->applicationCachePath);

        $ref = new ReflectionProperty(PackageManifest::class, 'packageManifest');
        $ref->setAccessible(true);
        $ref->setValue($packageManifest, null);

        $manifest = (fn () => $this->{'getPackageManifest'}())->call($packageManifest);

        $this->assertEquals(['test' => 'data'], $manifest);
    }
}
