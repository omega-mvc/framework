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
use Tests\FixturesPathTrait;
use Throwable;

use function file_exists;
use function file_get_contents;
use function ob_get_clean;
use function ob_start;
use function unlink;

/**
 * Test suite for "make" console commands that require a database connection.
 *
 * This test class verifies the behavior of code generation commands that
 * depend on database metadata, such as generating models from existing
 * tables and schemas.
 *
 * It bootstraps a real database connection, creates the required schema,
 * and configures the application container in order to simulate a realistic
 * execution environment for database-driven make commands.
 *
 * All generated artifacts are written to writable fixtures and removed
 * during teardown to ensure test isolation and repeatability.
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
#[CoversClass(Connection::class)]
#[CoversClass(Query::class)]
#[CoversClass(Application::class)]
#[CoversClass(MakeCommand::class)]
#[CoversClass(PDO::class)]
#[CoversClass(Schema::class)]
final class MakeCommandsWithDatabaseTest extends AbstractTestDatabase
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
        $this->createUserSchema();

        $this->app = new Application(__DIR__);
        $this->app->set('environment', 'dev');
        new Schema($this->app);
        new PDO($this->app);
        $this->app->set(Connection::class, $this->pdo);
        $this->app->set('Schema', $this->schema);
        $this->app->set('dsn.sql', $this->env);
        $this->app->set('Query', fn () => new Query($this->pdo));
        $this->app->set('path.model', $this->setFixturePath(slash(path: '/fixtures/application-write/console/commands/')));
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

        if (file_exists($client = $this->setFixturePath(slash(path: '/fixtures/application-write/console/commands/Client.php')))) {
            unlink($client);
        }
    }

    /**
     * Test it can call make command model with success.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable Thrown when any unexpected error occurs during database access,
     *                    file generation, or runtime evaluation.
     */
    public function testItCanCallMakeCommandModelWithSuccess()
    {
        $makeModel = new MakeCommand(['omega', 'make:model', 'Client', '--table-name', 'users']);

        ob_start();
        $exit = $makeModel->make_model();
        ob_get_clean();

        $this->assertEquals(0, $exit);

        $file = $this->setFixturePath(slash(path: '/fixtures/application-write/console/commands/Client.php'));
        $this->assertTrue(file_exists($file));

        $model = file_get_contents($file);
        $this->assertTrue(Str::contains($model, 'protected string $' . "tableName  = 'users'"));
        $this->assertTrue(Str::contains($model, 'protected string $' . "primaryKey = 'user'"));
    }
}
