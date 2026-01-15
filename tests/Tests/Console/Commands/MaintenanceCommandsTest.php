<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Console\Commands\MaintenanceCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;

use function file_exists;
use function filemtime;
use function ob_get_clean;
use function ob_start;
use function unlink;

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
     */
    /**
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
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
