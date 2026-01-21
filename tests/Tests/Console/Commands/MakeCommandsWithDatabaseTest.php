<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Connection;
use Omega\Database\Query\Query;
use Omega\Application\Application;
use Omega\Console\Commands\MakeCommand;
use Omega\Support\Facades\PDO;
use Omega\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;
use Tests\Database\AbstractTestDatabase;
use Omega\Text\Str;
use Throwable;

use function file_exists;
use function file_get_contents;
use function ob_get_clean;
use function ob_start;
use function unlink;

#[CoversClass(Connection::class)]
#[CoversClass(Query::class)]
#[CoversClass(Application::class)]
#[CoversClass(MakeCommand::class)]
#[CoversClass(PDO::class)]
#[CoversClass(Schema::class)]
final class MakeCommandsWithDatabaseTest extends AbstractTestDatabase
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
     * @throws CircularAliasException
     */
    protected function setUp(): void
    {
        $this->createConnection();
        $this->createUserSchema();

        $this->app = new Application(__DIR__);
        $this->app->set('environment', 'dev');
        new Schema($this->app);
        new PDO($this->app);
        $this->app->set(Connection::class, $this->pdo);
        $this->app->set('Schema', $this->schema);
        $this->app->set('dsn.sql', $this->env);
        $this->app->set('Query', fn () => new Query($this->pdo));
        $this->app->set('path.model', slash(path: __DIR__ . '/fixtures/'));
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

        if (file_exists($client =  slash(path: __DIR__ . '/fixtures/Client.php'))) {
            unlink($client);
        }
    }

    /**
     * Test it can call make command model with success.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws EntryNotFoundException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function testItCanCallMakeCommandModelWithSuccess()
    {
        $makeModel = new MakeCommand(['omega', 'make:model', 'Client', '--table-name', 'users']);

        ob_start();
        $exit = $makeModel->make_model();
        ob_get_clean();

        $this->assertEquals(0, $exit);

        $file = slash(path: __DIR__ . '/fixtures/Client.php');
        $this->assertTrue(file_exists($file));

        $model = file_get_contents($file);
        $this->assertTrue(Str::contains($model, 'protected string $' . "tableName  = 'users'"));
        $this->assertTrue(Str::contains($model, 'protected string $' . "primaryKey = 'user'"));
    }
}
