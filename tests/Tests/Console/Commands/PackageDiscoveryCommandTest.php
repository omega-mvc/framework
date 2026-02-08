<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Console\Commands;

use Exception;
use Omega\Application\Application;
use Omega\Console\Commands\PackageDiscoveryCommand;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Support\PackageManifest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

use function file_exists;
use function ob_get_clean;
use function ob_start;
use function unlink;

/**
 * Test suite for the PackageDiscovery console command.
 *
 * This class verifies the behavior of the `PackageDiscoveryCommand`,
 * which is responsible for discovering and caching package information
 * for the application. The tests ensure that the command correctly
 * generates the packages cache file and interacts properly with
 * the `PackageManifest` service.
 *
 * Each test runs in isolation using a real `Application` instance
 * configured with filesystem fixtures. Output buffering is used to
 * capture command output, while assertions check return codes and
 * the creation of expected cache files.
 *
 * The `tearDown()` method ensures that any generated package cache
 * file is removed after each test to maintain a clean test environment
 * and avoid side effects between tests.
 *
 * This suite ensures that package discovery behaves reliably under
 * normal execution conditions and that caching is performed correctly.
 *
 * @category   Tests
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(PackageDiscoveryCommand::class)]
#[CoversClass(CircularAliasException::class)]
final class PackageDiscoveryCommandTest extends TestCase
{
    use FixturesPathTrait;

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
        $file = $this->setFixturePath(slash(path: '/fixtures/application-write/bootstrap/cache/packages.php'));

        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Test it can create config file.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws Exception Thrown when a generic error occurred.
     */
    public function testItCanCreateConfigFile(): void
    {
        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-write/')));

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
