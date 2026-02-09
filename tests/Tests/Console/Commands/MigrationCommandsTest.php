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

use Omega\Application\Application;
use Omega\Console\Commands\MigrationCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Connection;
use Omega\Database\DatabaseManager;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Schema\Table\Create;
use Omega\Support\Facades\AbstractFacade;
use Omega\Support\Facades\Schema;
use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Tests\Database\AbstractTestDatabase;
use Tests\FixturesPathTrait;

/**
 * Integration tests for database migration console commands.
 *
 * This test class verifies the behavior of the migration-related console
 * commands, including running migrations, refreshing, resetting, rolling back,
 * and executing database-level operations such as create, drop, and show.
 *
 * The tests run against a real database connection using the
 * `AbstractTestDatabase` base class and ensure that migrations are correctly
 * discovered, executed, tracked, and reported.
 *
 * Vendor migrations, confirmation handling, and migration initialization
 * logic are also covered to ensure full command coverage and consistency.
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
#[CoversClass(DatabaseManager::class)]
#[CoversClass(Connection::class)]
#[CoversClass(SchemaConnection::class)]
#[CoversClass(Create::class)]
#[CoversClass(Application::class)]
#[CoversClass(MigrationCommand::class)]
#[CoversClass(AbstractFacade::class)]
#[CoversClass(Schema::class)]
#[CoversClass(Str::class)]
final class MigrationCommandsTest extends AbstractTestDatabase
{
    use FixturesPathTrait;

    /**
     * The application instance used during migration command tests.
     *
     * This property holds a fully configured `Application` container with
     * database connections, schema services, and migration paths registered.
     * It is initialized in `setUp()` and flushed during `tearDown()` to ensure
     * test isolation and avoid state leakage between test cases.
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
     */
    protected function setUp(): void
    {
        $this->createConnection();

        $this->app = new Application(__DIR__);
        $this->app->set('path.migration', $this->setFixturePath(slash(path: '/fixtures/application-read/console/database/migration/')));
        $this->app->set('environment', 'dev');
        $this->app->set(Connection::class, fn () => $this->pdo);
        $this->app->set(SchemaConnection::class, fn () => $this->pdoSchema);
        $this->app->set('Schema', fn () => $this->schema);
        $this->app->set('dsn.sql', fn () => $this->env);
        $this->app->set(DatabaseManager::class, fn () => $this->db);

        AbstractFacade::setFacadeBase($this->app);
        Schema::table('migration', function (Create $column) {
            $column('migration')->varchar(100)->notNull();
            $column('batch')->int(4)->notNull();

            $column->unique('migration');
        })->execute();
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
        Schema::drop()->table('migration')->ifExists()->execute();
        MigrationCommand::flushVendorMigrationPaths();
        $this->app->flush();
    }

    /**
     * Test it can run migration return success and success migrate.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     */
    public function testItCanRunMigrationReturnSuccessAndSuccessMigrate(): void
    {
        $migrate = new MigrationCommand(['omega', 'migrate']);
        ob_start();
        $exit = $migrate->main();
        $out  = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, '2023_08_07_181000_users'));
        $this->assertTrue(Str::contains($out, 'DONE'));
    }

    /**
     * Test it can run migration fresh return success and success migrate.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRunMigrationFreshReturnSuccessAndSuccessMigrate(): void
    {
        $migrate = new MigrationCommand(['omega', 'migrate:fresh']);
        ob_start();
        $exit = $migrate->fresh(true);
        $out  = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, 'success drop database `testing_db`'));
        $this->assertTrue(Str::contains($out, 'success create database `testing_db`'));
        $this->assertTrue(Str::contains($out, '2023_08_07_181000_users'));
        $this->assertTrue(Str::contains($out, 'DONE'));
    }

    /**
     * Test it can run migration reset return success and success migrate.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     */
    public function testItCanRunMigrationResetReturnSuccessAndSuccessMigrate(): void
    {
        $migrate = new MigrationCommand(['omega', 'migrate:reset']);
        ob_start();
        $exit = $migrate->reset();
        $out  = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, '2023_08_07_181000_users'));
        $this->assertTrue(Str::contains($out, 'DONE'));
    }

    /**
     * Test it can run migration refresh return success and success migrate.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     */
    public function testItCanRunMigrationRefreshReturnSuccessAndSuccessMigrate(): void
    {
        $migrate = new MigrationCommand(['omega', 'migrate:refresh']);
        ob_start();
        $exit = $migrate->refresh();
        $out  = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, '2023_08_07_181000_users'));
        $this->assertTrue(Str::contains($out, 'DONE'));
    }

    /**
     * Test it can run migration rollback return success and success migrate.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRunMigrationRollbackReturnSuccessAndSuccessMigrate(): void
    {
        $migrate = new MigrationCommand(['omega', 'migrate:rollback', '--batch=0']);
        ob_start();
        $exit = $migrate->rollback();
        $out  = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, '2023_08_07_181000_users'));
        $this->assertTrue(Str::contains($out, 'DONE'));
    }

    /**
     * Test it can run database create.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRunDatabaseCreate(): void
    {
        $migrate = new MigrationCommand(['omega', 'db:create']);
        ob_start();
        $exit = $migrate->databaseCreate(true);
        $out  = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, 'success create database `testing_db`'));
    }

    /**
     * Test it can run database show.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRunDatabaseShow(): void
    {
        $migrate = new MigrationCommand(['omega', 'db:show']);
        ob_start();
        $exit = $migrate->databaseShow();
        $out  = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, 'migration'));
    }

    /**
     * Test it can run database drop.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRunDatabaseDrop(): void
    {
        $migrate = new MigrationCommand(['omega', 'db:drop']);
        ob_start();
        $exit = $migrate->databaseDrop(true);
        $out  = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, 'success drop database `testing_db`'));
    }

    /**
     * Test it can run migrate init.
     *
     * @return void
     */
    public function testItCanRunMigrateInit(): void
    {
        $migrate = new MigrationCommand(['omega', 'migrate:init']);
        ob_start();
        $exit    = $migrate->initializeMigration();
        $out     = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, 'Migration table already exist on your database table.'));
    }

    /**
     * Test it can pass confirmation using options yes.
     *
     * @return void
     */
    public function testItCanPassConfirmationUsingOptionYes(): void
    {
        $confirmation = (fn () => $this->{'confirmation'}('message?'))->call(new MigrationCommand(['omega', 'db:create'], ['yes' => true]));
        $this->assertTrue($confirmation);
    }

    /**
     * Test it can run migration from vendor.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     */
    public function testItCanRunMigrationFromVendor(): void
    {
        $migrate = new MigrationCommand(['omega', 'migrate']);
        MigrationCommand::addVendorMigrationPath($this->setFixturePath(slash(path: '/fixtures/application-read/console/database/vendor-migration/')));
        ob_start();
        $exit = $migrate->main();
        $out  = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, '2023_08_07_181000_users'));
        $this->assertTrue(Str::contains($out, '2024_06_12_070600_clients'));
        $this->assertTrue(Str::contains($out, 'DONE'));
    }
}
