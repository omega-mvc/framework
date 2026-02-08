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

use Omega\Console\Commands\MaintenanceCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function file_exists;
use function filemtime;
use function ob_get_clean;
use function ob_start;
use function unlink;

/**
 * Test suite for the Maintenance console commands.
 *
 * This class verifies the behavior of the `MaintenanceCommand` for
 * putting the application into maintenance mode (`down`) and bringing it
 * back up (`up`). It ensures that the appropriate files are created or
 * removed in the storage directory, that repeated executions behave
 * correctly, and that failure scenarios are handled gracefully.
 *
 * Each test runs in isolation using the application instance provided
 * by `AbstractTestCommand`. Output buffering is used to capture console
 * output for assertion, while file existence and modification times are
 * used to validate the side effects of commands.
 *
 * The `tearDown()` method ensures any generated maintenance files are
 * cleaned up after each test to maintain a consistent test environment.
 *
 * This suite helps guarantee that maintenance mode functionality
 * behaves reliably under different scenarios, including fresh executions,
 * repeated executions, and failure conditions.
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
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(MaintenanceCommand::class)]
final class MaintenanceCommandsTest extends AbstractTestCommand
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
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function tearDown(): void
    {
        if (file_exists($down = $this->app->get('path.storage') . slash(path: 'app/down'))) {
            unlink($down);
        }

        if (file_exists($maintenance = $this->app->get('path.storage') . 'app/maintenance.php')) {
            unlink($maintenance);
        }
        parent::tearDown();
    }

    /**
     * Test it can make down maintenance mode.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanMakeDownMaintenanceMode(): void
    {
        $down = new MaintenanceCommand(['down']);

        $this->assertFileDoesNotExist($this->app->get('path.storage') . slash(path: 'app/down'));
        $this->assertFileDoesNotExist($this->app->get('path.storage') . slash(path: 'app/maintenance.php'));

        ob_start();
        $this->assertSuccess($down->down());
        ob_get_clean();

        $this->assertFileExists($this->app->get('path.storage') . slash(path: 'app/down'));
        $this->assertFileExists($this->app->get('path.storage') . slash('app/maintenance.php'));
    }

    /**
     * Test it can make down maintenance mode fresh down config.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanMakeDownMaintenanceModeFreshDownConfig(): void
    {
        $command = new MaintenanceCommand(['command']);
        ob_start();
        $command->down();

        $start = 0;

        if (file_exists($down = $this->app->get('path.storage') . slash(path: 'app/down'))) {
            $start = filemtime($down);
        }

        $command->down();
        $end = filemtime($down);
        ob_get_clean();

        $this->assertGreaterThanOrEqual($end, $start);
        $this->assertFileExists($this->app->get('path.storage') . slash(path: 'app/down'));
        $this->assertFileExists($this->app->get('path.storage') . slash(path: 'app/maintenance.php'));
    }

    /**
     * Test it can make down maintenance mode fail.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanMakeDownMaintenanceModeFail(): void
    {
        $down = new MaintenanceCommand(['down']);

        ob_start();
        $this->assertSuccess($down->down());
        $this->assertFails($down->down());
        ob_get_clean();
    }

    /**
     * Test iti can make up maintenance mode.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanMakeUpMaintenanceMode(): void
    {
        $command = new MaintenanceCommand(['up']);

        ob_start();
        $command->down();

        $this->assertFileExists($this->app->get('path.storage') . slash(path: 'app/down'));
        $this->assertFileExists($this->app->get('path.storage') . slash(path: 'app/maintenance.php'));
        $this->assertSuccess($command->up());

        ob_get_clean();
    }

    /**
     * Test it cn make up maintenance mode but fail.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanMakeUpMaintenanceModeButFail(): void
    {
        $command = new MaintenanceCommand(['up']);

        ob_start();
        $this->assertFails($command->up());
        ob_get_clean();
    }
}
