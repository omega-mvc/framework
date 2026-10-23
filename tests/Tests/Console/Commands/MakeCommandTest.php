<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MakeCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

use const DIRECTORY_SEPARATOR;

#[CoversClass(MakeCommand::class)]
final class MakeCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: string}
     */
    private function makeCommandFileApp(): array
    {
        $base = sys_get_temp_dir() . '/omegamkc-' . bin2hex(random_bytes(4));

        $save = $base . '/save';
        mkdir($save, 0777, true);

        $app = new Application($base);
        $app->set('path.command', $save);

        return [$app, $save];
    }

    public function testCreatesACommandFileForMakeCommand(): void
    {
        [$app, $save] = $this->makeCommandFileApp();

        $command = new MakeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $expected = 'Command [app' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR
            . 'Commands' . DIRECTORY_SEPARATOR . 'UserProfileCommand.php] created successfully.';

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString($expected, $result->getDisplay());
        $this->assertFileExists($save . '/UserProfileCommand.php');

        $app->flush();
    }

    public function testReplacesTheKebabVariableInTheGeneratedCommand(): void
    {
        [$app, $save] = $this->makeCommandFileApp();

        $command = new MakeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserAdminExport']);

        $content = (string) file_get_contents($save . '/UserAdminExportCommand.php');

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString("name: 'app:user-admin-export'", $content);
        $this->assertStringContainsString('class UserAdminExportCommand extends AbstractCommand', $content);
        $this->assertStringNotContainsString('#!', $content);

        $app->flush();
    }

    public function testReportsThatTheCommandFileAlreadyExists(): void
    {
        [$app, $save] = $this->makeCommandFileApp();
        file_put_contents($save . '/UserCommand.php', 'x');

        $command = new MakeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('already exists.', $result->getDisplay());

        $app->flush();
    }

    public function testRejectsANonStringNameArgument(): void
    {
        [$app] = $this->makeCommandFileApp();

        $command = new MakeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 42]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "name" argument must be a string.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenThePathCommandBindingIsNotAString(): void
    {
        [$app] = $this->makeCommandFileApp();
        $app->set('path.command', 123);

        $command = new MakeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.command" binding must resolve to a string path.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testFailsWhenTheTargetDirectoryCannotBeCreated(): void
    {
        $base = sys_get_temp_dir() . '/omegamkc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $blocker = $base . 'blocker';
        touch($blocker);
        $app->set('path.command', $blocker);

        $command = new MakeCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run(['name' => 'User']);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Unable to create directory', $result->getDisplay());

        $app->flush();
    }
}
