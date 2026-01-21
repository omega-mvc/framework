<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Exception;
use Omega\Application\Application;
use Omega\Console\Commands\PackageDiscoveryCommand;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Support\PackageManifest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_exists;
use function ob_get_clean;
use function ob_start;
use function unlink;

#[CoversClass(Application::class)]
#[CoversClass(PackageDiscoveryCommand::class)]
#[CoversClass(CircularAliasException::class)]
class PackageDiscoveryCommandTest extends TestCase
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
        if (file_exists($file = dirname(__FILE__) . slash(path: '/fixtures/app1/bootstrap/cache/packages.php'))) {
            @unlink($file);
        }
    }

    /**
     * Test it an create config file.
     *
     * @return void
     * @throws CircularAliasException
     * @throws Exception
     */
    public function testItCanCreateConfigFile(): void
    {
        $app = new Application(dirname(__FILE__) . slash(path:'/fixtures/app1/'));

        // overwrite PackageManifest has been set in Application before.
        $app->set(PackageManifest::class, fn () => new PackageManifest(
            basePath: $app->get('path.base'),
            applicationCachePath: $app->getApplicationCachePath(),
            vendorPath: '/package/'
        ));

        $discovery = new PackageDiscoveryCommand(['omega', 'package:discovery']);
        ob_start();
        $out = $discovery->discovery($app);
        ob_get_clean();

        $this->assertEquals(0, $out);

        $app->flush();
    }
}
