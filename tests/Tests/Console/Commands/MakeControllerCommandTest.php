<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MakeControllerCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

use const DIRECTORY_SEPARATOR;

#[CoversClass(MakeControllerCommand::class)]
final class MakeControllerCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: string}
     */
    private function makeControllerFileApp(): array
    {
        $base = sys_get_temp_dir() . '/omegamkc-' . bin2hex(random_bytes(4));

        $save = $base . '/save';
        mkdir($save, 0777, true);

        $app = new Application($base);
        $app->set('path.controller', $save);

        return [$app, $save];
    }

    public function testCreatesAControllerFileForMakeController(): void
    {
        [$app, $save] = $this->makeControllerFileApp();

        $command = new MakeControllerCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $expected = 'Controller [app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR
            . 'Controllers' . DIRECTORY_SEPARATOR . 'UserProfileController.php] created successfully.';

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString($expected, $result->getDisplay());
        $this->assertFileExists($save . '/UserProfileController.php');

        $app->flush();
    }

    public function testReplacesTheKebabVariableInTheGeneratedController(): void
    {
        [$app, $save] = $this->makeControllerFileApp();

        $command = new MakeControllerCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserAdminExport']);

        $content = (string) file_get_contents($save . '/UserAdminExportController.php');

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString("view('user-admin-export'", $content);
        $this->assertStringContainsString('class UserAdminExportController extends AbstractController', $content);
        $this->assertStringNotContainsString('#!', $content);

        $app->flush();
    }

    public function testReportsThatTheControllerFileAlreadyExists(): void
    {
        [$app, $save] = $this->makeControllerFileApp();
        file_put_contents($save . '/UserController.php', 'x');

        $command = new MakeControllerCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('already exists.', $result->getDisplay());

        $app->flush();
    }

    public function testRejectsANonStringNameArgument(): void
    {
        [$app] = $this->makeControllerFileApp();

        $command = new MakeControllerCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 42]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "name" argument must be a string.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenThePathControllerBindingIsNotAString(): void
    {
        [$app] = $this->makeControllerFileApp();
        $app->set('path.controller', 123);

        $command = new MakeControllerCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.controller" binding must resolve to a string path.',
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
        $app->set('path.controller', $blocker);

        $command = new MakeControllerCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run(['name' => 'User']);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Unable to create directory', $result->getDisplay());

        $app->flush();
    }
}
