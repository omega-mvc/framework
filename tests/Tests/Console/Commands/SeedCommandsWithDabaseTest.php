<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\SeedCommand;
use Omega\Database\Connection;
use Omega\Support\Facades\DB;
use Omega\Support\Facades\PDO as FacadesPDO;
use Omega\Support\Facades\Schema;
use Tests\Database\AbstractTestDatabase;
use Omega\Text\Str;

final class SeedCommandsWithDabaseTest extends AbstractTestDatabase
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
        $this->createUserSchema();

        // load seeder classes
        require_once slash(path: __DIR__ . '/fixtures/database/seeders/BasicSeeder.php');
        require_once slash(path: __DIR__ . '/fixtures/database/seeders/UserSeeder.php');
        require_once slash(path: __DIR__ . '/fixtures/database/seeders/ChainSeeder.php');
        require_once slash(path: __DIR__ . '/fixtures/database/seeders/CustomNamespaceSeeder.php');

        $this->app = new Application(__DIR__);
        $this->app->set('path.seeder', slash(path: __DIR__ . '/fixtures/database/seeders/'));
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
     * @test
     *
     * @group database
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
