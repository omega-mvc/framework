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
use Omega\Container\Exceptions\CircularAliasException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Application\Application;
use Omega\Text\Str;
use Tests\FixturesPathTrait;

use function explode;

/**
 * Class AbstractTestCommand
 *
 * Provides a base test case for console command tests in the Omega framework.
 *
 * This class sets up a fresh Application instance for each test, configuring
 * all required paths (views, controllers, services, models, commands, config,
 * migrations, seeders, storage) pointing to test fixtures. It also provides
 * helper methods for common assertions related to command execution:
 * - `assertSuccess` for verifying successful exit codes
 * - `assertFails` for verifying failure exit codes
 * - `assertContain` for checking if output contains expected text
 *
 * Tear down ensures the Application instance is flushed and unset to avoid
 * side effects between tests.
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
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Str::class)]
abstract class AbstractTestCommand extends TestCase
{
    use FixturesPathTrait;

    /**
     * The application instance used for running console command tests.
     *
     * This property holds an instance of `Omega\Application\Application` initialized
     * in the `setUp()` method before each test. It provides access to paths, services,
     * and configuration needed by the commands under test. It is reset to `null` in
     * `tearDown()` to avoid side effects between tests.
     *
     * @var Application|null
     */
    protected ?Application $app;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws Exception Throw if a generic error occurred.
     */
    protected function setUp(): void
    {
        $this->app = new Application($this->setFixtureBasePath());

        $this->app->set('path.view', __DIR__ . slash(path: '/fixtures/'));
        $this->app->set('path.controller', __DIR__ . slash(path: '/fixtures/'));
        $this->app->set('path.services', __DIR__ . slash(path: '/fixtures/'));
        $this->app->set('path.model', slash(path: '/fixtures/'));
        $this->app->set('path.command', __DIR__ . slash(path: '/fixtures/'));
        $this->app->set('path.config', __DIR__ . slash(path: '/fixtures/'));
        $this->app->set('path.migration', __DIR__ . slash(path: '/fixtures/migration/'));
        $this->app->set('path.seeder', __DIR__ . slash('/fixtures/seeders/'));
        $this->app->set('path.storage', $this->setFixturePath(slash(path: '/fixtures/application-write/storage/')));
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
        $this->app->flush();
        $this->app = null;
    }

    /**
     * @return string[]
     */
    protected function argv(string $argv): array
    {
        return explode(' ', $argv);
    }

    protected function assertSuccess(int $code): void
    {
        Assert::assertEquals(0, $code, 'Command exit with success code');
    }

    protected function assertFails(int $code): void
    {
        Assert::assertGreaterThan(0, $code, 'Command exit with fail code');
    }

    public function assertContain(string $contain, string $in): void
    {
        Assert::assertTrue(Str::contains($in, $contain), "This " . $contain . " is contained in " . $in);
    }
}
