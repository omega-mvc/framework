<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MaintenanceUpCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

use const DIRECTORY_SEPARATOR;

#[CoversClass(MaintenanceUpCommand::class)]
final class MaintenanceUpCommandTest extends TestCase
{
    public function testReportsAnErrorWhenTheApplicationIsAlreadyLive(): void
    {
        $base = sys_get_temp_dir() . '/omegamu-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $command = new MaintenanceUpCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Application is already live.', $result->getDisplay());

        $app->flush();
    }

    public function testRemovesTheMaintenanceFilesAndBringsTheApplicationBackOnline(): void
    {
        $base = sys_get_temp_dir() . '/omegamu-' . bin2hex(random_bytes(4));
        $appDir = $base . '/storage/app' . DIRECTORY_SEPARATOR;
        mkdir($appDir, 0777, true);
        touch($appDir . 'maintenance.php');
        touch($appDir . 'down');

        $app = new Application($base);
        $command = new MaintenanceUpCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Application is now live.', $result->getDisplay());
        $this->assertFileDoesNotExist($appDir . 'maintenance.php');
        $this->assertFileDoesNotExist($appDir . 'down');

        $app->flush();
    }

    public function testReportsAnErrorWhenTheStoragePathIsNotAString(): void
    {
        $base = sys_get_temp_dir() . '/omegamu-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.storage', 123);

        $command = new MaintenanceUpCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.storage" binding must resolve to a string path.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsAnErrorWhenAMaintenanceFileCannotBeRemoved(): void
    {
        $base = sys_get_temp_dir() . '/omegamu-' . bin2hex(random_bytes(4));
        $appDir = $base . '/storage/app' . DIRECTORY_SEPARATOR;
        mkdir($appDir . 'maintenance.php', 0777, true);

        $app = new Application($base);
        $command = new MaintenanceUpCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);
        $result = (new CommandTester($command))->run([]);
        restore_error_handler();

        $path = $appDir . 'maintenance.php';

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("Failed to remove the maintenance file '$path'.", $result->getDisplay());
        $this->assertStringContainsString("Please remove it manually at: $path", $result->getDisplay());
        $this->assertFileExists($path);

        $app->flush();
    }
}
