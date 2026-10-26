<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MakeMigrationCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(MakeMigrationCommand::class)]
final class MakeMigrationCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: string}
     */
    private function makeMigrationApp(): array
    {
        $base = sys_get_temp_dir() . '/omegamig-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $dir = $base . '/migrations/';
        mkdir($dir, 0777, true);

        $app = new Application($base);
        $app->set('path.migrations', $dir);

        return [$app, $dir];
    }

    public function testCreatesAMigrationFileForMakeMigration(): void
    {
        [$app, $dir] = $this->makeMigrationApp();

        $command = new MakeMigrationCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'CreateUsersTable']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Making migration...', $result->getDisplay());
        $this->assertStringContainsString('Success! Migration file created:', $result->getDisplay());
        $this->assertNotEmpty(glob($dir . '/*_createuserstable.php'));

        $app->flush();
    }

    public function testUsesTheUpdateStubForMakeMigrationUpdate(): void
    {
        [$app, $dir] = $this->makeMigrationApp();

        $command = new MakeMigrationCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'AddAgeToUsers', '--update' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Success! Migration file created:', $result->getDisplay());
        $this->assertNotEmpty(glob($dir . '/*_addagetousers.php'));

        $app->flush();
    }

    public function testRequiresATableNameWhenTheArgumentIsEmpty(): void
    {
        [$app] = $this->makeMigrationApp();

        $this->expectException(RuntimeException::class);

        $command = new MakeMigrationCommand();
        $command->app = $app;

        (new CommandTester($command))->run(['name' => ''], ['']);
    }

    public function testFailsWhenThePathMigrationsBindingIsNotAString(): void
    {
        [$app] = $this->makeMigrationApp();
        $app->set('path.migrations', 123);

        $command = new MakeMigrationCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'CreateUsersTable']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.migrations" binding must resolve to a string path.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testAsksForTheTableNameWhenTheArgumentIsEmpty(): void
    {
        [$app, $dir] = $this->makeMigrationApp();

        $command = new MakeMigrationCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => ''], ['Users']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertNotEmpty(glob($dir . '/*_users.php'));

        $app->flush();
    }

    public function testFailsWhenTheStubFileIsMissing(): void
    {
        [$app] = $this->makeMigrationApp();

        $command = new MakeMigrationCommand(sys_get_temp_dir() . '/omega-missing-stubs/');
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'CreateUsersTable']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Stub not found at:', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenTheStubFileCannotBeRead(): void
    {
        [$app] = $this->makeMigrationApp();

        $stubDir = sys_get_temp_dir() . '/omega-stubs-' . bin2hex(random_bytes(4)) . '/';
        mkdir($stubDir, 0777, true);
        file_put_contents($stubDir . 'migration.stub', '__table__');
        chmod($stubDir . 'migration.stub', 0000);

        $command = new MakeMigrationCommand($stubDir);
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run(['name' => 'CreateUsersTable']);

        restore_error_handler();

        chmod($stubDir . 'migration.stub', 0644);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Unable to read stub at:', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenTheMigrationFileCannotBeWritten(): void
    {
        $base = sys_get_temp_dir() . '/omegamig-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $blocker = $base . 'blocker';
        touch($blocker);
        $app->set('path.migrations', $blocker . '/migrations');

        $command = new MakeMigrationCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run(['name' => 'CreateUsersTable']);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("Can't create migration file in:", $result->getDisplay());

        $app->flush();
    }
}
