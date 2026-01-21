<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Console\Commands\MakeCommand;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
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
     * @throws CircularAliasException
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists($commandConfig = __DIR__ . slash(path: '/fixtures/command.php'))) {
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

        if (file_exists($commandConfig = __DIR__ . slash(path: '/fixtures/command.php'))) {
            unlink($commandConfig);
        }

        if (file_exists($assetController = __DIR__ . slash(path: '/fixtures/IndexController.php'))) {
            unlink($assetController);
        }

        if (file_exists($view = __DIR__ . slash(path: '/fixtures/welcome.template.php'))) {
            unlink($view);
        }

        if (file_exists($service = __DIR__ . slash(path: '/fixtures/ApplicationService.php'))) {
            unlink($service);
        }

        if (file_exists($command = __DIR__ . slash(path: '/fixtures/CacheCommand.php'))) {
            unlink($command);
        }

        $migration = __DIR__ . slash(path: '/fixtures/migration/');
        array_map('unlink', glob("$migration/*.php"));
    }

    /**
     * Test it can call make command controller with success.
     *
     * @return void
     * @throws CircularAliasException
     * @throws BindingResolutionException
     * @throws EntryNotFoundException
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     *
     *
     */
    public function testItCanCallMakeCommandControllerWithSuccess(): void
    {
        $makeCommand = new MakeCommand($this->argv('omega make:controller Index'));
        ob_start();
        $exit = $makeCommand->make_controller();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = __DIR__ . slash(path: '/fixtures/IndexController.php');
        $this->assertTrue(file_exists($file));

        $class = file_get_contents($file);
        $this->assertContain('class IndexController extends AbstractController', $class);
        $this->assertContain('public function handle(): Response', $class);
    }

    /**
     * Teest it can call make command controller with fails.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
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
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testItCanCallMakeCommandViewWithSuccess(): void
    {
        $makeCommand = new MakeCommand($this->argv('omega make:view welcome'));
        ob_start();
        $exit = $makeCommand->make_view();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = __DIR__ . slash(path: '/fixtures/welcome.template.php');
        $this->assertTrue(file_exists($file));

        $view = file_get_contents($file);
        $this->assertContain('<title>Document</title>', $view);
    }

    /**
     * Test it can call make command view with fails.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
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
     * @test
     */
    /**public function itCanCallMakeCommandServiceWithSuccess()
    {
        $make_service = new MakeCommand($this->argv('cli make:service Application'));
        ob_start();
        $exit = $make_service->make_services();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = __DIR__ . '/assets/ApplicationService.php';
        $this->assertTrue(file_exists($file));

        $service = file_get_contents($file);
        $this->assertContain('class ApplicationService extends Service', $service);
    }*/

    /**
     * @test
     */
    /**public function itCanCallMakeCommandServiceWithFails()
    {
        $make_service = new MakeCommand($this->argv('omega make:service Asset'));
        ob_start();
        $exit = $make_service->make_services();
        ob_get_clean();

        $this->assertFails($exit);
    }*/

    /**
     * Test it can call make command a commands with success.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testItCanCallMakeCommandACommandsWithSuccess(): void
    {
        $makeCommand = new MakeCommand($this->argv('omega make:command Cache'));
        ob_start();
        $exit = $makeCommand->make_command();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = __DIR__ . slash(path: '/fixtures/CacheCommand.php');
        $this->assertTrue(file_exists($file));

        $command = file_get_contents($file);
        $this->assertContain('class CacheCommand extends AbstractCommand', $command);
    }

    /**
     * Test it can call make command a commands with fails.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
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
     * @throws ContainerExceptionInterface
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
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
