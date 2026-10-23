<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\DatabaseWipeCommand;
use Omega\Database\Schema\SchemaConnection;
use Omega\Facade\AbstractFacade;
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

#[CoversClass(DatabaseWipeCommand::class)]
final class DatabaseWipeCommandTest extends TestCase
{
    public function testAbortsWhenTheUserDoesNotConfirmTheDropInProduction(): void
    {
        $base = sys_get_temp_dir() . '/omegadw-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);
        $app->set(SchemaConnection::class, $conn = new ScriptedSchemaConnection('omega_test'));
        $conn->whenQueryContains('information_schema.schemata', [['total' => '1']]);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseWipeCommand();
        $command->app = $app;

        $tester = new CommandTester($command);
        $tester->setInputs(['n']);

        $result = $tester->run(['--database' => 'omega_test'], ['n'], true);

        $this->assertSame(Command::INVALID, $result->statusCode);
        $this->assertStringContainsString('The application is in PRODUCTION.', $result->getDisplay());
        $this->assertStringContainsString('Operation aborted.', $result->getDisplay());

        $app->flush();
    }

    public function testDropsTheDatabaseAfterTheUserConfirmsInProduction(): void
    {
        $base = sys_get_temp_dir() . '/omegadw-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $schema = new FakeSchema();
        $app->set('Schema', $schema);
        $app->set('dsn.sql', ['driver' => 'mysql']);
        $app->set(SchemaConnection::class, $conn = new ScriptedSchemaConnection('omega_test'));
        $conn->whenQueryContains('information_schema.schemata', [['total' => '1']]);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseWipeCommand();
        $command->app = $app;

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);

        $result = $tester->run(['--database' => 'omega_test'], ['y'], true);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Trying to drop database `omega_test`...', $result->getDisplay());
        $this->assertStringContainsString('Successfully dropped database `omega_test`', $result->getDisplay());
        $this->assertSame('omega_test', $schema->last->database);
        $this->assertTrue($schema->last->ifExists);

        $app->flush();
    }

    public function testReportsWhenTheDatabaseDoesNotExist(): void
    {
        $base = sys_get_temp_dir() . '/omegadw-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('dsn.sql', ['driver' => 'mysql']);
        $app->set(SchemaConnection::class, new ScriptedSchemaConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseWipeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--no-interact' => true, '--database' => 'missing_db']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'Database `missing_db` does not exist, nothing to drop.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testDropsTheDatabaseWithoutConfirmationInNoInteractMode(): void
    {
        $base = sys_get_temp_dir() . '/omegadw-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $schema = new FakeSchema();
        $app->set('Schema', $schema);
        $app->set('dsn.sql', ['driver' => 'mysql']);
        $app->set(SchemaConnection::class, $conn = new ScriptedSchemaConnection('omega_test'));
        $conn->whenQueryContains('information_schema.schemata', [['total' => '1']]);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseWipeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--no-interact' => true, '--database' => 'omega_test']);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringNotContainsString('The application is in PRODUCTION.', $display);
        $this->assertStringContainsString('Successfully dropped database `omega_test`', $display);

        $app->flush();
    }

    public function testReportsWhenTheDatabaseCannotBeDropped(): void
    {
        $base = sys_get_temp_dir() . '/omegadw-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $schema = new FakeSchema();
        $schema->fail();
        $app->set('Schema', $schema);
        $app->set('dsn.sql', ['driver' => 'mysql']);
        $app->set(SchemaConnection::class, $conn = new ScriptedSchemaConnection('omega_test'));
        $conn->whenQueryContains('information_schema.schemata', [['total' => '1']]);

        AbstractFacade::setFacadeBase($app);

        $command = new DatabaseWipeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--no-interact' => true, '--database' => 'omega_test']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Cannot drop database `omega_test`', $result->getDisplay());

        $app->flush();
    }
}
