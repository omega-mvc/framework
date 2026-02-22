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

use Omega\Config\ConfigRepository;
use Omega\Console\Commands\HelpCommand;
use Omega\Console\Commands\ServeCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function ob_get_clean;
use function ob_start;

/**
 * Class HelpCommandsTest
 *
 * This test class verifies the behavior of the HelpCommand in the console application.
 * It ensures that the help system correctly displays command usage, lists registered
 * commands, handles command-specific help, and supports dynamically registered commands.
 * Each test sets up the application environment and registers necessary commands to
 * simulate real console scenarios.
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
#[CoversClass(ConfigRepository::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(HelpCommand::class)]
#[CoversClass(ServeCommand::class)]
final class HelpCommandsTest extends AbstractTestCommand
{
    /**
     * Test suite for the console help command.
     *
     * This class verifies the behavior of the help system used by console commands.
     * It ensures that help output, command listings, and command-specific help
     * information are correctly generated based on the commands registered in
     * the application configuration.
     *
     * The tests cover multiple registration scenarios, including:
     * - Commands registered via configuration arrays.
     * - Commands registered by providing a command class.
     * - Mixed and legacy command registration formats.
     *
     * The suite also validates error handling when requesting help for unknown
     * commands or when insufficient input is provided.
     */
    private array $command = [];

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

        $this->app->set('config', fn () => new ConfigRepository([
            'commands' => [$this->command],
        ]));
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

        $this->command = [];
    }

    /**
     * Test it can call help command main.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallHelpCommandMain(): void
    {
        $this->command = [
            [
                'cmd'       => ['-h', '--help'],
                'mode'      => 'full',
                'class'     => HelpCommand::class,
                'fn'        => 'main',
            ],
        ];

        $helpCommand = new HelpCommand(['omega', '--help']);
        ob_start();
        $exit = $helpCommand->main();
        ob_get_clean();

        $this->assertSuccess($exit);
    }

    /**
     * Test it can call help command main with register another command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallHelpCommandMainWithRegisterAnotherCommand(): void
    {
        $this->command = [
            [
                'pattern' => 'test',
                'fn'      => [RegisterHelpCommand::class, 'main'],
            ],
        ];

        $helpCommand = new HelpCommand(['omega', '--help']);

        ob_start();
        $exit = $helpCommand->main();
        $out  = ob_get_clean();

        $this->assertSuccess($exit);
        $this->assertContain('some test will appear in test', $out);
        $this->assertContain('this also will display in test', $out);
    }

    /**
     * Test it can call help command main with register another command using class.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallHelpCommandMainWithRegisterAnotherCommandUsingClass(): void
    {
        $this->command = [
            ['class' => RegisterHelpCommand::class],
        ];

        $helpCommand = new HelpCommand(['omega', '--help']);

        // use old style commandMaps
        ob_start();
        $exit = $helpCommand->main();
        $out  = ob_get_clean();

        $this->assertSuccess($exit);
        $this->assertContain('some test will appear in test', $out);
        $this->assertContain('this also will display in test', $out);
    }

    /**
     * Test it can call help command list.
     *
     * @return void
     */
    public function testItCanCallHelpCommandCommandList(): void
    {
        $helpCommand = new HelpCommand(['omega', '--list']);

        ob_start();
        $exit = $helpCommand->commandList();
        ob_get_clean();

        $this->assertSuccess($exit);
    }

    /**
     * Test it can call help command list with register another command.
     *
     * @return void
     */
    public function testItCanCallHelpCommandCommandListWithRegisterAnotherCommand(): void
    {
        $this->command = [
            [
                'pattern' => 'unit:test',
                'fn'      => [RegisterHelpCommand::class, 'main'],
            ],
        ];

        $helpCommand = new HelpCommand(['omega', '--list']);

        ob_start();
        $exit = $helpCommand->commandList();
        $out  = ob_get_clean();

        $this->assertContain('unit:test', $out);
        $this->assertContain('Tests\Console\Commands\RegisterHelpCommand', $out);
        $this->assertSuccess($exit);
    }

    /**
     * Test it can call help command help.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallHelpCommandCommandHelp(): void
    {
        $helpCommand = new HelpCommand(['omega', 'help', 'serve']);
        ob_start();
        $exit = $helpCommand->commandHelp();
        $out  = ob_get_clean();

        $this->assertSuccess($exit);
        $this->assertContain('Serve server with port number (default 8000)', $out);
    }

    /**
     * Test it can help command help but not found.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallHelpCommandCommandHelpButNoFound(): void
    {
        $helpCommand =  new HelpCommand(['omega', 'help', 'main']);
        ob_start();
        $exit = $helpCommand->commandHelp();
        $out  = ob_get_clean();

        $this->assertFails($exit);
        $this->assertContain('Help for `main` command not found', $out);
    }

    /**
     * Test it can call help command helo but no result.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallHelpCommandCommandHelpButNoResult(): void
    {
        $helpCommand =  new HelpCommand(['omega', 'help']);
        ob_start();
        $exit = $helpCommand->commandHelp();
        $out  = ob_get_clean();

        $this->assertFails($exit);
        $this->assertContain('php omega help <command_name>', $out);
    }
}
