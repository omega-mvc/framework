<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MigrateRollbackCommand;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\TestCase;

#[CoversClass(MigrateRollbackCommand::class)]
final class MigrateRollbackCommandTest extends TestCase
{
    private function makeApp(string $prefix): Application
    {
        $base = sys_get_temp_dir() . '/' . $prefix . '-' . bin2hex(random_bytes(4));
        mkdir($base . '/database/migrations', 0777, true);

        copy(
            __DIR__ . '/Fixtures/migrations/create_users_table.php',
            $base . '/database/migrations/create_users_table.php'
        );

        ScriptedConnection::resetShared();

        $app = new Application($base);
        $app->set('database', new ScriptedConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        return $app;
    }

    /**
     * @param list<string> $migrations Migration fixture file names to copy.
     */
    private function makeAppWith(string $prefix, array $migrations): Application
    {
        $base = sys_get_temp_dir() . '/' . $prefix . '-' . bin2hex(random_bytes(4));
        mkdir($base . '/database/migrations', 0777, true);

        foreach ($migrations as $migration) {
            copy(
                __DIR__ . '/Fixtures/migrations/' . $migration,
                $base . '/database/migrations/' . $migration
            );
        }

        ScriptedConnection::resetShared();

        $app = new Application($base);
        $app->set('database', new ScriptedConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        return $app;
    }

    public function testTreatsAMissingTableDuringTheRollbackAsSuccess(): void
    {
        $app = $this->makeAppWith('omegarollback', ['drop_missing_table.php']);

        $command = new MigrateRollbackCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--batch' => '1', '--take' => '1']);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('DONE', $display);

        $app->flush();
    }

    public function testReportsTheFailureWhenARollbackQueryThrows(): void
    {
        $app = $this->makeAppWith('omegarollback', ['failing_down_table.php']);

        $command = new MigrateRollbackCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--batch' => '1', '--take' => '1']);

        $display = $result->getDisplay();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Unable to run: DROP TABLE broken_down', $display);
        $this->assertStringContainsString('FAIL', $display);

        $app->flush();
    }

    public function testFailsWhenTheBatchOptionIsMissing(): void
    {
        $app = $this->makeApp('omegarollback');

        $command = new MigrateRollbackCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('batch is required.', $result->getDisplay());

        $app->flush();
    }

    public function testRollsBackTheRequestedBatch(): void
    {
        $app = $this->makeApp('omegarollback');

        $command = new MigrateRollbackCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--batch' => '1', '--take' => '2']);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Rolling 2 back migrations.', $display);

        $app->flush();
    }

    public function testFallsBackToTheDefaultTakeWhenTheValueIsNotNumeric(): void
    {
        $app = $this->makeApp('omegarollback');

        $command = new MigrateRollbackCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--batch' => 'not-a-number', '--take' => 'abc']);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Rolling 0 back migrations.', $display);

        $app->flush();
    }

    public function testNormalisesANegativeTakeValue(): void
    {
        $app = $this->makeApp('omegarollback');

        $command = new MigrateRollbackCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--batch' => '2', '--take' => '-3']);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Rolling back migrations.', $display);

        $app->flush();
    }
}
