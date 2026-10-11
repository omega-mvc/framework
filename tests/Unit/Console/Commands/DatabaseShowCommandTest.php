<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\DatabaseShowCommand;
use Omega\Database\Schema\SchemaConnection;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\Console\Commands\Fixtures\ScriptedSchemaConnection;
use Tests\TestCase;

use function bin2hex;
use function mkdir;
use function random_bytes;
use function sys_get_temp_dir;

#[CoversClass(DatabaseShowCommand::class)]
final class DatabaseShowCommandTest extends TestCase
{
    public function testListsTheTablesOfTheDatabaseWithTheirSizes(): void
    {
        $base = sys_get_temp_dir() . '/omegads-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $connection = new ScriptedConnection('omega_test');
        $connection->whenQueryContains('information_schema.tables', [
            ['table_name' => 'users', 'create_time' => '2026-01-01 12:00:00', 'size' => '3'],
            ['table_name' => 'posts', 'create_time' => '2026-01-02 12:00:00', 'size' => '8'],
        ]);
        $app->set('database', $connection);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseShowCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--database' => 'omega_test']);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Showing database: omega_test', $display);
        $this->assertStringContainsString('users', $display);
        $this->assertStringContainsString('3 MB', $display);
        $this->assertStringContainsString('posts', $display);
        $this->assertStringContainsString('8 MB', $display);
        $this->assertStringContainsString('2026-01-01 12:00:00', $display);

        $app->flush();
    }

    public function testReportsWhenTheDatabaseIsEmpty(): void
    {
        $base = sys_get_temp_dir() . '/omegads-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $connection = new ScriptedConnection('omega_test');
        $connection->whenQueryContains('information_schema.tables', []);
        $app->set('database', $connection);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseShowCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--database' => 'omega_test']);

        $this->assertSame(Command::INVALID, $result->statusCode);
        $this->assertStringContainsString('Database is empty, try to run migration.', $result->getDisplay());

        $app->flush();
    }

    public function testUsesTheConfiguredSchemaConnectionToResolveTheDefaultDatabaseName(): void
    {
        $base = sys_get_temp_dir() . '/omegads-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set(SchemaConnection::class, new ScriptedSchemaConnection('shop_db'));

        $connection = new ScriptedConnection('shop_db');
        $connection->whenQueryContains('information_schema.tables', [
            ['table_name' => 'products', 'create_time' => '2026-03-01 10:00:00', 'size' => '1'],
        ]);
        $app->set('database', $connection);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseShowCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Showing database: shop_db', $result->getDisplay());

        $app->flush();
    }

    public function testShowsTheColumnsOfTheGivenTableWithTheirAttributes(): void
    {
        $base = sys_get_temp_dir() . '/omegads-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $connection = new ScriptedConnection('omega_test');
        $connection->whenQueryContains('INFORMATION_SCHEMA.COLUMNS', [
            ['COLUMN_NAME' => 'id', 'COLUMN_TYPE' => 'int(11)', 'COLUMN_KEY' => 'PRI', 'IS_NULLABLE' => 'NO'],
            ['COLUMN_NAME' => 'name', 'COLUMN_TYPE' => 'varchar(255)', 'COLUMN_KEY' => null, 'IS_NULLABLE' => 'YES'],
        ]);
        $app->set('database', $connection);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseShowCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--database' => 'omega_test', '--table-name' => 'users']);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Columns of table: users', $display);
        $this->assertStringContainsString('id', $display);
        $this->assertStringContainsString('int(11)', $display);
        $this->assertStringContainsString('primary', $display);
        $this->assertStringContainsString('name', $display);
        $this->assertStringContainsString('varchar(255)', $display);
        $this->assertStringContainsString('nullable', $display);

        $app->flush();
    }

    public function testReportsWhenTheGivenTableDoesNotExistOrHasNoColumns(): void
    {
        $base = sys_get_temp_dir() . '/omegads-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $connection = new ScriptedConnection('omega_test');
        $connection->whenQueryContains('INFORMATION_SCHEMA.COLUMNS', []);
        $app->set('database', $connection);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseShowCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--database' => 'omega_test', '--table-name' => 'ghosts']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Table `ghosts` does not exist or has no columns.', $result->getDisplay());

        $app->flush();
    }
}
