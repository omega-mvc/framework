<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MigrateRunCommand;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\TestCase;

#[CoversClass(MigrateRunCommand::class)]
final class MigrateRunCommandTest extends TestCase
{
    private function migrationFixtureDir(string $base): string
    {
        $dir = $base . '/database/migrations';
        mkdir($dir, 0777, true);

        copy(
            __DIR__ . '/Fixtures/migrations/create_users_table.php',
            $dir . '/create_users_table.php'
        );

        return $dir;
    }

    public function testAbortsWhenRunningInProductionWithoutConfirmation(): void
    {
        $base = sys_get_temp_dir() . '/omegarun-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new MigrateRunCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::INVALID, $result->statusCode);

        $app->flush();
    }

    public function testRunsThePendingMigrationsWhenForced(): void
    {
        $base = sys_get_temp_dir() . '/omegarun-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->migrationFixtureDir($base);

        $app = new Application($base);
        $app->set('database', new ScriptedConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateRunCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Running migrations', $display);
        $this->assertStringContainsString('create_users_table', $display);
        $this->assertStringContainsString('DONE', $display);

        $app->flush();
    }

    public function testPrintsTheMigrationSqlWhenRunningInDryRunMode(): void
    {
        $base = sys_get_temp_dir() . '/omegarun-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->migrationFixtureDir($base);

        $app = new Application($base);
        $app->set('database', new ScriptedConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateRunCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true, '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('CREATE TABLE users (id INT)', $result->getDisplay());

        $app->flush();
    }

    public function testReportsNothingToMigrateWhenEverythingIsAlreadyRun(): void
    {
        $base = sys_get_temp_dir() . '/omegarun-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->migrationFixtureDir($base);

        $connection = new ScriptedConnection('omega_test');
        $connection->whenQueryContains('migration', [
            ['migration' => 'create_users_table', 'batch' => 1],
        ]);

        $app = new Application($base);
        $app->set('database', $connection);

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateRunCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Nothing to migrate.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenTheMigrationsPathIsNotAString(): void
    {
        $base = sys_get_temp_dir() . '/omegarun-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.migrations', 123);
        $app->set('database', new ScriptedConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateRunCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.migrations" binding must resolve to a string path.',
            $display
        );
        $this->assertStringContainsString('Nothing to migrate.', $display);

        $app->flush();
    }
}
