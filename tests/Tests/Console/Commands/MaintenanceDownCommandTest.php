<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MaintenanceDownCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

use const DIRECTORY_SEPARATOR;

#[CoversClass(MaintenanceDownCommand::class)]
final class MaintenanceDownCommandTest extends TestCase
{
    public function testReportsAnErrorWhenTheApplicationIsAlreadyUnderMaintenanceMode(): void
    {
        $base = sys_get_temp_dir() . '/omegamdd-' . bin2hex(random_bytes(4));
        mkdir($base . '/storage/app/maintenance.php', 0777, true);

        $app = new Application($base);
        $command = new MaintenanceDownCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Application is already under maintenance mode.', $result->getDisplay());

        $app->flush();
    }

    public function testPutsTheApplicationIntoMaintenanceModeWithDefaultOptions(): void
    {
        $base = sys_get_temp_dir() . '/omegamdd-' . bin2hex(random_bytes(4));
        mkdir($base . '/storage/app', 0777, true);

        $app = new Application($base);
        $command = new MaintenanceDownCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Application is now in maintenance mode.', $result->getDisplay());

        $downPath        = $base . '/storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'down';
        $maintenancePath = $base . '/storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'maintenance.php';
        $stub            = (string) file_get_contents(dirname(__DIR__, 4) . '/src/Omega/Console/stubs/maintenance.stub');
        $down            = (string) file_get_contents($downPath);

        $this->assertFileExists($downPath);
        $this->assertStringContainsString('503', $down);
        $this->assertStringContainsString('NULL', $down);
        $this->assertSame($stub, (string) file_get_contents($maintenancePath));

        $app->flush();
    }

    public function testCompilesTheProvidedRedirectRetryStatusAndTemplateOptionsIntoTheDownFile(): void
    {
        $base = sys_get_temp_dir() . '/omegamdd-' . bin2hex(random_bytes(4));
        mkdir($base . '/storage/app', 0777, true);

        $app = new Application($base);
        $command = new MaintenanceDownCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([
            '--redirect' => '/maintenance',
            '--retry'    => '120',
            '--status'   => '418',
            '--template' => '<h1>Under maintenance</h1>',
        ]);

        $down = (string) file_get_contents($base . '/storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'down');

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString("'redirect' => '/maintenance',", $down);
        $this->assertStringContainsString("'retry'    => '120',", $down);
        $this->assertStringContainsString("'status'   => 418,", $down);
        $this->assertStringContainsString("'template' => '<h1>Under maintenance</h1>',", $down);

        $app->flush();
    }

    public function testRejectsAnInvalidStatusCode(): void
    {
        $base = sys_get_temp_dir() . '/omegamdd-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $command = new MaintenanceDownCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--status' => '99']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The status code must be an integer between 100 and 599.', $result->getDisplay());

        $app->flush();
    }

    public function testRejectsANegativeRetryValue(): void
    {
        $base = sys_get_temp_dir() . '/omegamdd-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $command = new MaintenanceDownCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--status' => '200', '--retry' => '-2']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The retry value must be an integer greater than or equal to zero.', $result->getDisplay());

        $app->flush();
    }

    public function testReportsAnErrorWhenThePathStorageBindingIsNotAString(): void
    {
        $base = sys_get_temp_dir() . '/omegamdd-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.storage', 123);
        $command = new MaintenanceDownCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "path.storage" binding must resolve to a string path.', $result->getDisplay());

        $app->flush();
    }

    public function testReportsAnErrorWhenTheDownConfigurationCannotBeWritten(): void
    {
        $base = sys_get_temp_dir() . '/omegamdd-' . bin2hex(random_bytes(4));
        mkdir($base . '/w/app/down', 0777, true);

        $app = new Application($base);
        $app->set('path.storage', $base . '/w');
        $command = new MaintenanceDownCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);
        $result = (new CommandTester($command))->run([]);
        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Unable to write the maintenance mode configuration.', $result->getDisplay());

        $app->flush();
    }
}
