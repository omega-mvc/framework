<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MigrateResetCommand;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\TestCase;

#[CoversClass(MigrateResetCommand::class)]
final class MigrateResetCommandTest extends TestCase
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
        $base = sys_get_temp_dir() . '/omegareset-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new MigrateResetCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::INVALID, $result->statusCode);

        $app->flush();
    }

    public function testRollsBackEverythingWhenTheSilentOptionIsUsed(): void
    {
        $base = sys_get_temp_dir() . '/omegareset-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->migrationFixtureDir($base);

        $app = new Application($base);
        $app->set('database', new ScriptedConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateResetCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--silent' => true]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Rolling back all migrations', $display);
        $this->assertStringContainsString('create_users_table', $display);
        $this->assertStringContainsString('DONE', $display);

        $app->flush();
    }

    public function testPrintsTheRollbackSqlWhenRunningInDryRunMode(): void
    {
        $base = sys_get_temp_dir() . '/omegareset-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        ScriptedConnection::resetShared();
        $this->migrationFixtureDir($base);

        $app = new Application($base);
        $app->set('database', new ScriptedConnection('omega_test'));

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateResetCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true, '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('DROP TABLE users', $result->getDisplay());

        $app->flush();
    }
}
