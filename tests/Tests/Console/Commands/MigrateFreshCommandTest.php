<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MigrateFreshCommand;
use Omega\Database\Schema\Schema;
use Omega\Database\Schema\SchemaConnection;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\ConsoleHarness;
use Tests\Console\Commands\Fixtures\FakeSchema;
use Tests\Console\Commands\Fixtures\FakeSeeder;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\Console\Commands\Fixtures\ScriptedSchemaConnection;
use Tests\TestCase;

#[CoversClass(MigrateFreshCommand::class)]
final class MigrateFreshCommandTest extends TestCase
{
    private function freshMigrationsDir(string $base): string
    {
        $dir = $base . '/database/migrations';
        mkdir($dir, 0777, true);

        copy(
            __DIR__ . '/Fixtures/migrations/create_users_table.php',
            $dir . '/create_users_table.php'
        );

        return $dir;
    }

    public function testReportsWhenTheDbWipeCommandCannotBeDispatched(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new MigrateFreshCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            "Unable to execute command 'db:wipe': The application instance is not available.",
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testAbortsWhenTheProductionConfirmationIsDeclined(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new MigrateFreshCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([], ['n'], true);

        $this->assertSame(Command::INVALID, $result->statusCode);
        $this->assertStringContainsString(
            'Running migration/database in production? Continue?',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testPropagatesTheFailureWhenTheDatabaseDoesNotExist(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);
        $app->set(SchemaConnection::class, new ScriptedSchemaConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        [$application] = ConsoleHarness::make($app);

        $command = new MigrateFreshCommand();
        $command->app = $app;
        $command->setApplication($application);

        $result = (new CommandTester($command))->run(['--force' => true]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'Database `omega_test` does not exist, nothing to drop.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testDropsRecreatesAndMigratesTheDatabaseFromScratch(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->freshMigrationsDir($base);

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);

        $connection = new ScriptedSchemaConnection('omega_test');
        $connection->whenQueryContains('information_schema.schemata', [['total' => '1']]);
        $app->set(SchemaConnection::class, $connection);

        $app->set('database', new ScriptedConnection('omega_test'));

        $schema = new FakeSchema();
        $app->set('Schema', $schema);

        AbstractFacade::setFacadeBase($app);

        [$application] = ConsoleHarness::make($app);

        $command = new MigrateFreshCommand();
        $command->app = $app;
        $command->setApplication($application);

        $result = (new CommandTester($command))->run(['--force' => true]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Successfully dropped database `omega_test`', $display);
        $this->assertStringContainsString('Successfully created database `omega_test`', $display);
        $this->assertStringContainsString('Successfully created migration table.', $display);
        $this->assertStringContainsString('Running migration', $display);
        $this->assertStringContainsString('create_users_table', $display);
        $this->assertStringContainsString('DONE', $display);
        $this->assertSame('migration', $schema->last->table);

        $app->flush();
    }

    public function testPrintsTheMigrationSqlWhenRunningInDryRunMode(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->freshMigrationsDir($base);

        $app = new Application($base);

        $command = new MigrateFreshCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true, '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString(
            'CREATE TABLE users (id INT)',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsWhenThePathMigrationsBindingIsNotAString(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.migrations', 123);

        $command = new MigrateFreshCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true, '--dry-run' => true]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.migrations" binding must resolve to a string path.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testPropagatesTheFailureWhenTheDatabaseCannotBeCreated(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->freshMigrationsDir($base);

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);

        $connection = new class () extends ScriptedSchemaConnection {
            public function execute(): bool
            {
                $last = end($this->queries);

                if (is_string($last) && str_contains($last, 'CREATE DATABASE')) {
                    return false;
                }

                return parent::execute();
            }
        };
        $connection->whenQueryContains('information_schema.schemata', [['total' => '1']]);
        $app->set(SchemaConnection::class, $connection);

        $app->set('database', new ScriptedConnection('omega_test'));
        $app->set('Schema', new Schema($connection, 'omega_test'));

        AbstractFacade::setFacadeBase($app);

        [$application] = ConsoleHarness::make($app);

        $command = new MigrateFreshCommand();
        $command->app = $app;
        $command->setApplication($application);

        $result = (new CommandTester($command))->run(['--force' => true]);

        $display = $result->getDisplay();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Successfully dropped database `omega_test`', $display);
        $this->assertStringContainsString('Cannot create database `omega_test`', $display);

        $app->flush();
    }

    public function testPropagatesTheFailureWhenTheMigrationTableCannotBeCreated(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->freshMigrationsDir($base);

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);

        $connection = new class () extends ScriptedSchemaConnection {
            public function execute(): bool
            {
                $last = end($this->queries);

                if (is_string($last) && str_contains($last, 'CREATE TABLE')) {
                    return false;
                }

                return parent::execute();
            }
        };
        $connection->whenQueryContains('information_schema.schemata', [['total' => '1']]);
        $app->set(SchemaConnection::class, $connection);

        $app->set('database', new ScriptedConnection('omega_test'));
        $app->set('Schema', new Schema($connection, 'omega_test'));

        AbstractFacade::setFacadeBase($app);

        [$application] = ConsoleHarness::make($app);

        $command = new MigrateFreshCommand();
        $command->app = $app;
        $command->setApplication($application);

        $result = (new CommandTester($command))->run(['--force' => true]);

        $display = $result->getDisplay();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Successfully created database `omega_test`', $display);
        $this->assertStringContainsString('Migration table cannot be created.', $display);

        $app->flush();
    }

    public function testSkipsMissingMigrationDirectoriesWhenRunningInDryRunMode(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.migrations', $base . '/missing-migrations');

        $command = new MigrateFreshCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true, '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Running migration', $result->getDisplay());

        $app->flush();
    }

    public function testSeedsTheDatabaseAfterRunningTheMigrations(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        FakeSeeder::$runs = 0;
        FakeSeeder::$throw = null;

        $app = new Application($base);
        $app->set('environment', 'dev');
        $app->set('dsn.sql', ['driver' => 'mysql']);

        $connection = new ScriptedSchemaConnection('omega_test');
        $connection->whenQueryContains('information_schema.schemata', [['total' => '1']]);
        $app->set(SchemaConnection::class, $connection);

        $app->set('database', new ScriptedConnection('omega_test'));

        $schema = new FakeSchema();
        $app->set('Schema', $schema);

        AbstractFacade::setFacadeBase($app);

        [$application] = ConsoleHarness::make($app);

        $command = new MigrateFreshCommand();
        $command->app = $app;
        $command->setApplication($application);

        $result = (new CommandTester($command))->run([
            '--force'          => true,
            '--seed'           => true,
            '--seed-namespace' => FakeSeeder::class,
        ]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Running seeder: ' . FakeSeeder::class, $display);
        $this->assertStringContainsString('Success run seeder: ' . FakeSeeder::class, $display);
        $this->assertSame(1, FakeSeeder::$runs);

        FakeSeeder::$throw = null;

        $app->flush();
    }

    public function testMarksTheMigrationAsFailedWhenAQueryThrows(): void
    {
        $base = sys_get_temp_dir() . '/omegafresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->freshMigrationsDir($base);

        ScriptedSchemaConnection::shared('users_raw')->failNextExecute();

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);

        $connection = new ScriptedSchemaConnection('omega_test');
        $connection->whenQueryContains('information_schema.schemata', [['total' => '1']]);
        $app->set(SchemaConnection::class, $connection);

        $app->set('database', new ScriptedConnection('omega_test'));

        $schema = new FakeSchema();
        $app->set('Schema', $schema);

        AbstractFacade::setFacadeBase($app);

        [$application] = ConsoleHarness::make($app);

        $command = new MigrateFreshCommand();
        $command->app = $app;
        $command->setApplication($application);

        $result = (new CommandTester($command))->run(['--force' => true]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Scripted failure', $display);
        $this->assertStringContainsString('create_users_table', $display);
        $this->assertStringContainsString('FAIL', $display);

        $app->flush();
    }
}
