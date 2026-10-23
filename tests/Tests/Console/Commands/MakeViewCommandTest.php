<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MakeViewCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

use const DIRECTORY_SEPARATOR;

#[CoversClass(MakeViewCommand::class)]
final class MakeViewCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: string}
     */
    private function makeViewFileApp(): array
    {
        $base = sys_get_temp_dir() . '/omegamkc-' . bin2hex(random_bytes(4));

        $save = $base . '/save';
        mkdir($save, 0777, true);

        $app = new Application($base);
        $app->set('path.view', $save);

        return [$app, $save];
    }

    public function testCreatesAViewFileForMakeView(): void
    {
        [$app, $save] = $this->makeViewFileApp();

        $command = new MakeViewCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $expected = 'View file [resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
            . 'UserProfile.template.php] created successfully.';

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString($expected, $result->getDisplay());
        $this->assertFileExists($save . '/UserProfile.template.php');

        $content = (string) file_get_contents($save . '/UserProfile.template.php');
        $this->assertStringContainsString('<title>Document</title>', $content);
        $this->assertStringContainsString('<html lang="en">', $content);

        $app->flush();
    }

    public function testReportsThatTheViewFileAlreadyExists(): void
    {
        [$app, $save] = $this->makeViewFileApp();
        file_put_contents($save . '/User.template.php', 'x');

        $command = new MakeViewCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('already exists.', $result->getDisplay());

        $app->flush();
    }

    public function testRejectsANonStringNameArgument(): void
    {
        [$app] = $this->makeViewFileApp();

        $command = new MakeViewCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 42]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "name" argument must be a string.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenThePathViewBindingIsNotAString(): void
    {
        [$app] = $this->makeViewFileApp();
        $app->set('path.view', 123);

        $command = new MakeViewCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.view" binding must resolve to a string path.',
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
        $app->set('path.view', $blocker);

        $command = new MakeViewCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run(['name' => 'User']);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Unable to create directory', $result->getDisplay());

        $app->flush();
    }
}
