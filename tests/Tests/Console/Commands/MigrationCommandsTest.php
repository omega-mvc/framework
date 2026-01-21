<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Database\DatabaseManager;
use Omega\Database\Connection;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Schema\Table\Create;
use Omega\Application\Application;
use Omega\Console\Commands\MigrationCommand;
use Omega\Support\Facades\AbstractFacade;
use Omega\Support\Facades\Schema;
use Tests\Database\AbstractTestDatabase;
use Omega\Text\Str;

final class MigrationCommandsTest extends AbstractTestDatabase
{
    private Application $app;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->createConnection();

        $this->app = new Application(__DIR__);
        $this->app->set('path.migration', slash(path: __DIR__ . '/fixtures/database/migration/'));
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
     */
    public function testItCanPassConfirmationUsingOptionYes(): void
    {
        $confirmation = (fn () => $this->{'confirmation'}('message?'))->call(new MigrationCommand(['omega', 'db:create'], ['yes' => true]));
        $this->assertTrue($confirmation);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanRunMigrationFromVendor(): void
    {
        $migrate = new MigrationCommand(['omega', 'migrate']);
        MigrationCommand::addVendorMigrationPath(slash(path: __DIR__ . '/fixtures/database/vendor-migration/'));
        ob_start();
        $exit = $migrate->main();
        $out  = ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(Str::contains($out, '2023_08_07_181000_users'));
        $this->assertTrue(Str::contains($out, '2024_06_12_070600_clients'));
        $this->assertTrue(Str::contains($out, 'DONE'));
    }
}
