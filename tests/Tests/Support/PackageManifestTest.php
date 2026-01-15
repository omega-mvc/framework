<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase;
use Omega\Support\PackageManifest;

class PackageManifestTest extends TestCase
{
    private string $basePath             = __DIR__ . '/fixtures/app1/';
    private string $applicationCachePath = __DIR__ . '/fixtures/app1/bootstrap/cache/';
    private string $packageManifest      = __DIR__ . '/fixtures/app1/bootstrap/cache/packages.php';

    public function deleteAsset(): void
    {
        if (file_exists($this->packageManifest)) {
            @unlink($this->packageManifest);
        }
    }

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
        $this->deleteAsset();
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
        $this->deleteAsset();
    }

    /**
     * Test it can build.
     *
     * @return void
     */
    public function testItCanBuild(): void
    {
        $package_manifest = new PackageManifest($this->basePath, $this->applicationCachePath, slash(path: '/package/'));
        $package_manifest->build();

        $this->assertTrue(file_exists($this->packageManifest));
    }

    /**
     * Test it can get package manifest.
     *
     * @return void
     */
    public function testItCanGetPackageManifest(): void
    {
        $package_manifest = new PackageManifest($this->basePath, $this->applicationCachePath, slash(path: '/package/'));

        $manifest = (fn () => $this->{'getPackageManifest'}())->call($package_manifest);

        $this->assertEquals([
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
        ], $manifest);
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
}
