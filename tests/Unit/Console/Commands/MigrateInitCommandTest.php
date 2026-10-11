<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MigrateInitCommand;
use Omega\Database\Schema\SchemaConnection;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\FakeSchema;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\Console\Commands\Fixtures\ScriptedSchemaConnection;
use Tests\TestCase;

#[CoversClass(MigrateInitCommand::class)]
final class MigrateInitCommandTest extends TestCase
{
    public function testReportsWhenTheDatabaseDoesNotExist(): void
    {
        $base = sys_get_temp_dir() . '/omegainit-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);
        $app->set(SchemaConnection::class, new ScriptedSchemaConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateInitCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'Database `omega_test` does not exist, run `db:create` first.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsWhenTheMigrationTableAlreadyExists(): void
    {
        $base = sys_get_temp_dir() . '/omegainit-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);

        $connection = new ScriptedSchemaConnection('omega_test');
        $connection->whenQueryContains('information_schema.schemata', [['total' => '1']]);
        $app->set(SchemaConnection::class, $connection);

        $pdo = new ScriptedConnection('omega_test');
        $pdo->whenQueryContains('information_schema.tables', [['total' => '1']]);
        $app->set('database', $pdo);

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateInitCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString(
            'Migration table already exists in your database.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testCreatesTheMigrationTableWhenItIsMissing(): void
    {
        $base = sys_get_temp_dir() . '/omegainit-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);

        $connection = new ScriptedSchemaConnection('omega_test');
        $connection->whenQueryContains('information_schema.schemata', [['total' => '1']]);
        $app->set(SchemaConnection::class, $connection);

        $app->set('database', new ScriptedConnection('omega_test'));

        $schema = new FakeSchema();
        $app->set('Schema', $schema);

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateInitCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Successfully created migration table.', $result->getDisplay());
        $this->assertSame('migration', $schema->last->table);

        $app->flush();
    }

    public function testReportsWhenTheMigrationTableCannotBeCreated(): void
    {
        $base = sys_get_temp_dir() . '/omegainit-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);

        $connection = new ScriptedSchemaConnection('omega_test');
        $connection->whenQueryContains('information_schema.schemata', [['total' => '1']]);
        $app->set(SchemaConnection::class, $connection);

        $app->set('database', new ScriptedConnection('omega_test'));

        $schema = new FakeSchema();
        $schema->fail();
        $app->set('Schema', $schema);

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateInitCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Migration table cannot be created.', $result->getDisplay());

        $app->flush();
    }
}
