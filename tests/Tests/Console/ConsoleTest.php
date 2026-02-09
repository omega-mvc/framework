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

namespace Tests\Console;

use Exception;
use Omega\Application\Application;
use Omega\Console\Console;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Support\PackageManifest;
use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\Console\Support\NormalCommand;
use Tests\FixturesPathTrait;

/**
 * Integration tests for the Console component.
 *
 * This test suite verifies the behavior of the console command kernel,
 * ensuring that commands can be resolved, matched, suggested, and executed
 * correctly under different scenarios.
 *
 * Covered behaviors include:
 * - Resolving commands using full names, groups, prefixes, and patterns
 * - Handling commands without modes or main commands
 * - Matching commands via explicit matchers and regular expressions
 * - Providing meaningful error messages and suggestions for unknown commands
 * - Returning appropriate exit codes for success and failure cases
 * - Bootstrapping the console and application state correctly
 *
 * The tests rely on a real Application instance configured with
 * fixture-based paths and a controlled PackageManifest, in order to
 * simulate realistic runtime conditions while keeping the environment
 * isolated and deterministic.
 *
 * This class acts as a safety net for refactoring the Console subsystem,
 * ensuring that command resolution logic and user-facing behaviors remain
 * stable over time.
 *
 * @category  Tests
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Console::class)]
#[CoversClass(PackageManifest::class)]
#[CoversClass(Str::class)]
final class ConsoleTest extends TestCase
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
     * @var Application
     */
    private Application $app;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws Exception Thrown when a generic error occurred.
     */
    protected function setUp(): void
    {
        $this->app = new Application($this->setFixturePath(slash(path: '/fixtures/application-read/')));

        $this->app->set(PackageManifest::class, fn () => new PackageManifest(
            basePath: $this->app->get('path.base'),
            applicationCachePath: $this->app->getApplicationCachePath(),
            vendorPath: '/package/'
        ));
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
    }

    /**
     * Test it can call command using full command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallCommandUsingFullCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'use:full']);
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $hasContent = Str::contains($out, 'command has founded');
        $this->assertTrue($hasContent);
    }

    /**
     * Test it can call command using group command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallCommandUsingGroupCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'use:group']);
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $hasContent = Str::contains($out, 'command has founded');
        $this->assertTrue($hasContent);
    }

    /**
     * Test it can call command using start command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallCommandUsingStartCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'start:testing']);
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $hasContent = Str::contains($out, 'command has founded');
        $this->assertTrue($hasContent);
    }

    /**
     * Test it can call command using without mode command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallCommandUsingWithoutModeCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'use:without_mode']);
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $hasContent = Str::contains($out, 'command has founded');
        $this->assertTrue($hasContent);
    }

    /**
     * Test it can call command using without main command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallCommandUsingWithoutMainCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'use:without_main']);
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $hasContent = Str::contains($out, 'command has founded');
        $this->assertTrue($hasContent);
    }

    /**
     * Test it can call command using match command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallCommandUsingMatchCommand():void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'use:match']);
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $hasContent = Str::contains($out, 'command has founded');
        $this->assertTrue($hasContent);
    }

    /**
     * Test it can call command using pattern command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallCommandUsingPatternCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'use:pattern']);
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $hasContent = Str::contains($out, 'command has founded');
        $this->assertTrue($hasContent);
    }

    /**
     * Test it can call command using pattern group named.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallCommandUsingPatternGroupCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'pattern1']);
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $hasContent = Str::contains($out, 'command has founded');
        $this->assertTrue($hasContent);

        // 2
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'pattern2']);
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $hasContent = Str::contains($out, 'command has founded');
        $this->assertTrue($hasContent);
    }

    /**
     * Test it can call command with default options.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallCommandWithDefaultOption(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'use:default_option']);
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $hasContent = Str::contains($out, 'test');
        $this->assertTrue($hasContent);
    }

    /**
     * Test it can return nothing because command not found.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanReturnNothingBecauseCommandNotFound(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'test']);
        ob_get_clean();

        $this->assertEquals(1, $exit);
    }

    /**
     * Test it can return command not found because not closet another command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanReturnCommandNotFoundBecauseNotClosetAnotherCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'xzy']);
        $out     = ob_get_clean();

        $this->assertEquals(1, $exit);
        $condition =  Str::contains($out, 'Command "xzy" is not defined.');
        $this->assertTrue($condition);
    }

    /**
     * Test it can return suggestion command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanReturnSuggestionCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'use:patern']);
        $out     = ob_get_clean();

        $this->assertEquals(1, $exit);
        $condition =  Str::contains($out, 'Did you mean one of these?');
        $this->assertTrue($condition);
        $condition =  Str::contains($out, 'use:pattern');
        $this->assertTrue($condition);
    }

    /**
     * Test it can give closet command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanGivenClosetCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit    = $kernel->handle(['omega', 'use:']);
        $out     = ob_get_clean();

        $this->assertEquals(1, $exit);
        $condition =  Str::contains($out, 'use:full');
        $this->assertTrue($condition);
    }

    /**
     * Test it can bootstrap.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanBootstrap(): void
    {
        $this->assertFalse($this->app->bootstrapped);
        $this->app->make(Console::class)->bootstrap();
        $this->assertTrue($this->app->bootstrapped);
    }

    /**
     * Test it can call command.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallCommand(): void
    {
        $kernel = new NormalCommand($this->app);
        ob_start();
        $exit = $kernel->call('cli use:no-int-return');
        ob_get_clean();

        $this->assertEquals(0, $exit);
    }

    /**
     * Test it can get similar command.
     *
     * @return void
     */
    public function testItCanGetSimilarCommand(): void
    {
        $kernel = new Console($this->app);
        $result = (fn () => $this->{'getSimilarity'}('make:view', ['view:clear', 'make:view', 'make:controller']))->call($kernel);
        $this->assertArrayHasKey('make:view', $result);
        $this->assertArrayHasKey('make:controller', $result);
    }
}
