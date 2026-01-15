<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Config\ConfigRepository;
use Omega\Console\Commands\HelpCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;

use function ob_get_clean;
use function ob_start;

#[CoversClass(ConfigRepository::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(HelpCommand::class)]
final class HelpCommandsTest extends AbstractTestCommand
{
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
     */
    /**
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
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
