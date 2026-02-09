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
use Omega\Console\Commands\SeedCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Connection;
use Omega\Support\Facades\DB;
use Omega\Support\Facades\PDO as FacadesPDO;
use Omega\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\Database\AbstractTestDatabase;
use Omega\Text\Str;
use Tests\FixturesPathTrait;

use function ob_get_clean;
use function ob_start;

/**
 * Integration tests for database seeding console commands.
 *
 * This test class verifies the correct execution of database seeders
 * through the `db:seed` console command using a real database connection.
 * It ensures that seeders can be executed individually, via chained calls,
 * and using custom namespaces, while properly interacting with the
 * application container and database layer.
 *
 * The test suite bootstraps a full application instance for each test,
 * configures database services, loads fixture seeders, and validates
 * both command output and data insertion behavior.
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
#[CoversClass(SeedCommand::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Connection::class)]
#[CoversClass(DB::class)]
#[CoversClass(FacadesPDO::class)]
#[CoversClass(Schema::class)]
final class SeedCommandsWithDatabaseTest extends AbstractTestDatabase
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
     * @throws Exception Throw when a generic error occurred.
     */
    protected function setUp(): void
    {
        $this->createConnection();
        $this->createUserSchema();

        $seederPath = $this->setFixturePath(slash(path: '/fixtures/application-read/console/database/seeders/'));
        // load seeder classes
        require_once slash(path: $seederPath . 'BasicSeeder.php');
        require_once slash(path: $seederPath . 'UserSeeder.php');
        require_once slash(path: $seederPath . 'ChainSeeder.php');
        require_once slash(path: $seederPath . 'CustomNamespaceSeeder.php');

        $this->app = new Application($this->setFixtureBasePath());
        $this->app->set('path.seeder', $seederPath );
        $this->app->set('environment', 'dev');
        new Schema($this->app);
        new FacadesPDO($this->app);
        new DB($this->app);
        $this->app->set(Connection::class, $this->pdo);
        $this->app->set('Schema', $this->schema);
        $this->app->set('dsn.sql', $this->env);
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
        $this->dropConnection();
        $this->app->flush();
    }

    /**
     * Test it can run seeder.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRunSeeder(): void
    {
        $seeder = new SeedCommand(['omega', 'db:seed', '--class', 'BasicSeeder']);
        ob_start();
        $seeder->main();
        $out  = ob_get_clean();

        $this->assertTrue(Str::contains($out, 'seed for basic seeder'));
        $this->assertTrue(Str::contains($out, 'Success run seeder'));
    }

    /**
     * Test it can run seeder runner with real insert data.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRunSeederRunnerWithRealInsertData(): void
    {
        $seeder = new SeedCommand(['omega', 'db:seed', '--class', 'UserSeeder']);
        ob_start();
        $seeder->main();
        $out  = ob_get_clean();

        $this->assertTrue(Str::contains($out, 'Success run seeder'));
    }

    /**
     * Test it can run seeder with custom namespace
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRunSeederWithCustomNamespace(): void
    {
        $seeder = new SeedCommand(['omega', 'db:seed', '--name-space', 'CustomNamespaceSeeder']);
        ob_start();
        $seeder->main();
        $out  = ob_get_clean();

        $this->assertTrue(Str::contains($out, 'Success run seeder'));
    }

    /**
     * Test it can run seeder with call other.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRunSeederWithCallOther(): void
    {
        $seeder = new SeedCommand(['omega', 'db:seed', '--class', 'ChainSeeder']);
        ob_start();
        $seeder->main();
        $out  = ob_get_clean();

        $this->assertTrue(Str::contains($out, 'seed for basic seeder'));
        $this->assertTrue(Str::contains($out, 'Success run seeder'));
    }
}
