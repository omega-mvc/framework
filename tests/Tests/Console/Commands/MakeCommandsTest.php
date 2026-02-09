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

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Console\Commands\MakeCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function array_map;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function ob_get_clean;
use function ob_start;
use function unlink;

/**
 * Test suite for the "make" console commands.
 *
 * This test class verifies the correct behavior of all scaffold-related
 * console commands provided by the framework, such as generating controllers,
 * views, commands, and migrations.
 *
 * It ensures that:
 * - The appropriate files are created in the expected locations.
 * - Generated files contain the correct class definitions or templates.
 * - Commands properly handle both success and failure scenarios.
 * - Invalid or conflicting inputs result in controlled failures.
 *
 * The tests operate on writable application fixtures and clean up all
 * generated artifacts after each run to avoid side effects between tests.
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
#[CoversClass(MakeCommand::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
final class MakeCommandsTest extends AbstractTestCommand
{
    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $commandConfig = $this->setFixturePath(slash(path: '/fixtures/application-write/console/commands/command.php'));
        if (!file_exists($commandConfig)) {
            file_put_contents($commandConfig,
                '<?php return \array_merge(
                    // more command here
                );'
            );
        }
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

        $fixturePath = $this->setFixturePath(slash(path: '/fixtures/application-write/console/'));

        if (file_exists($commandConfig = slash(path: $fixturePath . 'commands/command.php'))) {
            unlink($commandConfig);
        }

        if (file_exists($assetController = slash(path: $fixturePath . 'commands/IndexController.php'))) {
            unlink($assetController);
        }

        if (file_exists($view = slash(path: $fixturePath . 'commands/welcome.template.php'))) {
            unlink($view);
        }

        if (file_exists($service = slash(path: $fixturePath . 'commands/ApplicationService.php'))) {
            unlink($service);
        }

        if (file_exists($command = slash(path: $fixturePath . 'commands/CacheCommand.php'))) {
            unlink($command);
        }

        $migration = slash(path: $fixturePath . 'database/migration/');
        array_map('unlink', glob("$migration/*.php"));
    }

    /**
     * Test it can call make command controller with success.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallMakeCommandControllerWithSuccess(): void
    {
        $makeCommand = new MakeCommand($this->argv('omega make:controller Index'));
        ob_start();
        $exit = $makeCommand->make_controller();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = $this->setFixturePath(slash(path: '/fixtures/application-write/console/commands/IndexController.php'));
        $this->assertTrue(file_exists($file));

        $class = file_get_contents($file);
        $this->assertContain('class IndexController extends AbstractController', $class);
        $this->assertContain('public function handle(): Response', $class);
    }

    /**
     * Teest it can call make command controller with fails.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallMakeCommandControllerWithFails(): void
    {
        $makeCommand = new MakeCommand($this->argv('omega make:controller Asset'));
        ob_start();
        $exit = $makeCommand->make_controller();
        ob_get_clean();

        $this->assertFails($exit);
    }

    /**
     * Test it can call make command view with success.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallMakeCommandViewWithSuccess(): void
    {
        $makeCommand = new MakeCommand($this->argv('omega make:view welcome'));
        ob_start();
        $exit = $makeCommand->make_view();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = $this->setFixturePath(slash(path: '/fixtures/application-write/console/commands/welcome.template.php'));
        $this->assertTrue(file_exists($file));

        $view = file_get_contents($file);
        $this->assertContain('<title>Document</title>', $view);
    }

    /**
     * Test it can call make command view with fails.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallMakeCommandViewWithFails(): void
    {
        $makeCommand = new MakeCommand($this->argv('omega make:view asset'));
        ob_start();
        $exit = $makeCommand->make_view();
        ob_get_clean();

        $this->assertFails($exit);
    }

    /**
     * Test it can call make command a commands with success.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallMakeCommandACommandsWithSuccess(): void
    {
        $makeCommand = new MakeCommand($this->argv('omega make:command Cache'));
        ob_start();
        $exit = $makeCommand->make_command();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = $this->setFixturePath(slash(path: '/fixtures/application-write/console/commands/CacheCommand.php'));
        $this->assertTrue(file_exists($file));

        $command = file_get_contents($file);
        $this->assertContain('class CacheCommand extends AbstractCommand', $command);
    }

    /**
     * Test it can call make command a commands with fails.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallMakeCommandACommandsWithFails(): void
    {
        $make_command = new MakeCommand($this->argv('omega make:command Asset'));
        ob_start();
        $exit = $make_command->make_command();
        ob_get_clean();

        $this->assertFails($exit);
    }

    /**
     * Test it can call make command migration with success.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanCallMakeCommandMigrationWithSuccess(): void
    {
        $make_command = new MakeCommand($this->argv('omega make:migration user'));
        ob_start();
        $exit = $make_command->make_migration();
        ob_get_clean();

        $this->assertSuccess($exit);

        $make_command = new MakeCommand($this->argv('omega make:migration guest --update'));
        ob_start();
        $exit = $make_command->make_migration();
        ob_get_clean();

        $this->assertSuccess($exit);
    }
}
