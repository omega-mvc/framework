<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\DatabaseCreateCommand;
use Omega\Database\Schema\SchemaConnection;
use Omega\Facade\AbstractFacade;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\FakeSchema;
use Tests\Console\Commands\Fixtures\ScriptedSchemaConnection;
use Tests\TestCase;

use function bin2hex;
use function mkdir;
use function random_bytes;
use function sys_get_temp_dir;

#[CoversClass(DatabaseCreateCommand::class)]
final class DatabaseCreateCommandTest extends TestCase
{
    public function testAbortsWhenTheUserDoesNotConfirmTheCreationInProduction(): void
    {
        $base = sys_get_temp_dir() . '/omegadb-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set(SchemaConnection::class, new ScriptedSchemaConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseCreateCommand();
        $command->app = $app;

        $tester = new CommandTester($command);
        $tester->setInputs(['n']);

        $result = $tester->run(['--database' => 'omega_test'], ['n'], true);

        $this->assertSame(Command::INVALID, $result->statusCode);
        $this->assertStringContainsString('The application is in PRODUCTION.', $result->getDisplay());
        $this->assertStringContainsString('Operation aborted.', $result->getDisplay());

        $app->flush();
    }

    public function testCreatesTheDatabaseAfterTheUserConfirmsInProduction(): void
    {
        $base = sys_get_temp_dir() . '/omegadb-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $schema = new FakeSchema();
        $app->set('Schema', $schema);
        $app->set(SchemaConnection::class, new ScriptedSchemaConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseCreateCommand();
        $command->app = $app;

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);

        $result = $tester->run(['--database' => 'omega_test'], ['y'], true);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Creating database `omega_test`...', $result->getDisplay());
        $this->assertStringContainsString('Successfully created database `omega_test`', $result->getDisplay());
        $this->assertSame('omega_test', $schema->last->database);

        $app->flush();
    }

    public function testSkipsTheConfirmationPromptWhenTheNoInteractOptionIsUsed(): void
    {
        $base = sys_get_temp_dir() . '/omegadb-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $schema = new FakeSchema();
        $app->set('Schema', $schema);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseCreateCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--no-interact' => true, '--database' => 'app_db']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Successfully created database `app_db`', $result->getDisplay());
        $this->assertSame('app_db', $schema->last->database);

        $app->flush();
    }

    public function testReportsWhenTheDatabaseAlreadyExists(): void
    {
        $base = sys_get_temp_dir() . '/omegadb-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $schema = new FakeSchema();
        $schema->throwing(new PDOException('SQLSTATE[42P01]: database exists'));
        $app->set('Schema', $schema);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseCreateCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--no-interact' => true, '--database' => 'app_db']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Database `app_db` already exists.', $result->getDisplay());

        $app->flush();
    }

    public function testRethrowsADatabaseExceptionThatIsNotAboutAnExistingDatabase(): void
    {
        $base = sys_get_temp_dir() . '/omegadb-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $schema = new FakeSchema();
        $schema->throwing(new PDOException('Unexpected connection issue'));
        $app->set('Schema', $schema);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseCreateCommand();
        $command->app = $app;

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Unexpected connection issue');

        (new CommandTester($command))->run([
            '--no-interact' => true,
            '--database'    => 'app_db',
        ]);

        $app->flush();
    }

    public function testReportsWhenTheDatabaseCannotBeCreated(): void
    {
        $base = sys_get_temp_dir() . '/omegadb-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $schema = new FakeSchema();
        $schema->fail();
        $app->set('Schema', $schema);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseCreateCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--no-interact' => true, '--database' => 'app_db']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Cannot create database `app_db`', $result->getDisplay());

        $app->flush();
    }
}
