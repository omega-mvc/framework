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

use Omega\Console\Commands\SeedCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

/**
 * Integration tests for the `SeedCommand` functionality.
 *
 * This test class verifies the behavior of the `make:seed` console command,
 * ensuring that seed files are correctly created, handled when they already
 * exist, and that failure cases are properly detected.
 *
 * It also manages the setup and teardown of the test environment, including
 * cleaning up generated seeder files to avoid side effects between tests.
 *
 * Seeder files tested include:
 * - `BaseSeeder.php` for normal creation.
 * - `ExistSeeder.php` for force-overwrite scenarios.
 * - Tests for command failure cases when no name is provided or the file exists.
 *
 * Each test method focuses on a specific aspect of the command:
 * - Successful seeder creation.
 * - Failure when required input is missing.
 * - Failure when attempting to create a seeder that already exists without `--force`.
 * - Successful overwrite when `--force` is used.
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
#[CoversClass(SeedCommand::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
final class SeedCommandsTest extends AbstractTestCommand
{
    use FixturesPathTrait;

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

        $path = $this->setFixturePath(
            slash(path: '/fixtures/application-write/console/database/seeders/')
        );

        $filesToRemove = [
            'BaseSeeder.php',
            'ExistSeeder.php',
        ];

        foreach ($filesToRemove as $file) {
            $fullPath = $path . $file;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    /**
     * Test it can call make command seeder with success.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallMakeCommandSeederWithSuccess(): void
    {
        $makeCommand = new SeedCommand($this->argv('omega make:seed BaseSeeder'));
        ob_start();
        $exit = $makeCommand->make();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = $this->setFixturePath(slash(path: '/fixtures/application-write/console/database/seeders/BaseSeeder.php'));
        $this->assertTrue(file_exists($file));

        $class = file_get_contents($file);
        $this->assertContain('class BaseSeeder extends AbstractSeeder', $class);
        $this->assertContain('public function run(): void', $class);
    }

    /**
     * Test it can call make command see with fails.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallMakeCommandSeedWithFails(): void
    {
        $makeCommand = new SeedCommand($this->argv('omega make:seed'));
        ob_start();
        $exit = $makeCommand->make();
        ob_get_clean();

        $this->assertFails($exit);
    }

    /**
     * Test it can call make command seed with fails file exists.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallMakeCommandSeedWithFailsFileExist(): void
    {
        app()->set('path.seeder', $this->setFixturePath(slash(path: '/fixtures/application-write/console/database/seeders/')));
        $makeCommand = new SeedCommand($this->argv('omega make:seed BasicSeeder'));
        ob_start();
        $exit = $makeCommand->make();
        ob_get_clean();

        $this->assertFails($exit);
    }

    /**
     * Test it can call make exist command seeder.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCallMakeExistCommandSeeder(): void
    {
        app()->set('path.seeder', $this->setFixturePath(slash(path: '/fixtures/application-write/console/database/seeders/')));
        $makeCommand = new SeedCommand($this->argv('omega make:seed ExistSeeder --force'));
        ob_start();
        $exit = $makeCommand->make();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = $this->setFixturePath(slash(path: '/fixtures/application-write/console/database/seeders/ExistSeeder.php'));
        $this->assertTrue(file_exists($file));

        $class = file_get_contents($file);
        $this->assertContain('class ExistSeeder extends AbstractSeeder', $class);
        $this->assertContain('public function run(): void', $class);
    }
}
