<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MakeSeedCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(MakeSeedCommand::class)]
final class MakeSeedCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: string}
     */
    private function makeSeederApp(): array
    {
        $base = sys_get_temp_dir() . '/omegaseeder-' . bin2hex(random_bytes(4));

        $save = $base . '/seeders';
        mkdir($save, 0777, true);

        $app = new Application($base);
        $app->set('path.seeder', $save);

        return [$app, $save];
    }

    public function testCreatesASeederFileForMakeSeeder(): void
    {
        [$app, $save] = $this->makeSeederApp();

        $command = new MakeSeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserSeeder']);

        $content = (string) file_get_contents($save . '/UserSeeder.php');

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Seeder [UserSeeder] created successfully.', $result->getDisplay());
        $this->assertFileExists($save . '/UserSeeder.php');
        $this->assertStringContainsString('namespace Database\\Seeders;', $content);
        $this->assertStringContainsString('extends AbstractSeeder', $content);

        $app->flush();
    }

    public function testRejectsANonStringNameArgumentForMakeSeeder(): void
    {
        [$app] = $this->makeSeederApp();

        $command = new MakeSeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 42]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "name" argument must be a string.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenThePathSeederBindingIsNotAString(): void
    {
        [$app] = $this->makeSeederApp();
        $app->set('path.seeder', 123);

        $command = new MakeSeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserSeeder']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "path.seeder" binding must resolve to a string path.', $result->getDisplay());

        $app->flush();
    }

    public function testReportsThatTheSeederAlreadyExistsWithoutForce(): void
    {
        [$app, $save] = $this->makeSeederApp();
        file_put_contents($save . '/UserSeeder.php', 'x');

        $command = new MakeSeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserSeeder']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Seeder [UserSeeder] already exists!', $result->getDisplay());

        $app->flush();
    }

    public function testOverwritesAnExistingSeederFileWithForce(): void
    {
        [$app, $save] = $this->makeSeederApp();
        file_put_contents($save . '/UserSeeder.php', 'old');

        $command = new MakeSeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserSeeder', '--force' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringNotContainsString('old', (string) file_get_contents($save . '/UserSeeder.php'));

        $app->flush();
    }

    public function testFailsWhenTheSeederFileCannotBeWritten(): void
    {
        $base = sys_get_temp_dir() . '/omegaseeder-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $blocker = $base . 'blocker';
        touch($blocker);
        $app->set('path.seeder', $blocker . '/seeders');

        $command = new MakeSeedCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run(['name' => 'UserSeeder']);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Failed to create seeder [UserSeeder].', $result->getDisplay());

        $app->flush();
    }
}
