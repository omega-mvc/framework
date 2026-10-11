<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MigrateRefreshCommand;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\ConsoleHarness;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\TestCase;

#[CoversClass(MigrateRefreshCommand::class)]
final class MigrateRefreshCommandTest extends TestCase
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
        $base = sys_get_temp_dir() . '/omegarefresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new MigrateRefreshCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::INVALID, $result->statusCode);

        $app->flush();
    }

    public function testReportsWhenTheMigrateResetCommandCannotBeDispatched(): void
    {
        $base = sys_get_temp_dir() . '/omegarefresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new MigrateRefreshCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            "Unable to execute command 'migrate:reset': The application instance is not available.",
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testRollsBackAndReRunsAllMigrationsWhenForced(): void
    {
        $base = sys_get_temp_dir() . '/omegarefresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->migrationFixtureDir($base);

        $app = new Application($base);
        $app->set('database', new ScriptedConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        [$application] = ConsoleHarness::make($app);

        $command = new MigrateRefreshCommand();
        $command->app = $app;
        $command->setApplication($application);

        $result = (new CommandTester($command))->run(['--force' => true]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Rolling back all migrations', $display);
        $this->assertStringContainsString('create_users_table', $display);
        $this->assertStringContainsString('DONE', $display);

        $app->flush();
    }

    public function testPrintsTheMigrationSqlWithoutExecutingWhenRefreshingInDryRunMode(): void
    {
        $base = sys_get_temp_dir() . '/omegarefresh-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->migrationFixtureDir($base);

        $app = new Application($base);
        $app->set('database', new ScriptedConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        [$application] = ConsoleHarness::make($app);

        $command = new MigrateRefreshCommand();
        $command->app = $app;
        $command->setApplication($application);

        $result = (new CommandTester($command))->run(['--force' => true, '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('CREATE TABLE users (id INT)', $result->getDisplay());

        $app->flush();
    }
}
