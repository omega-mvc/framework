<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\AbstractMakeCommand;
use Omega\Console\Commands\MakeCommand;
use Omega\Console\Commands\MakeControllerCommand;
use Omega\Console\Commands\MakeExceptionCommand;
use Omega\Console\Commands\MakeMiddlewareCommand;
use Omega\Console\Commands\MakeProviderCommand;
use Omega\Console\Commands\MakeViewCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\NoMakeCommand;
use Tests\TestCase;

require_once __DIR__ . '/Fixtures/NoMakeCommand.php';

#[CoversClass(AbstractMakeCommand::class)]
final class MakeCommandsTest extends TestCase
{
    /**
     * @return array{0: Application, 1: string}
     */
    private function makeCommandApp(string $binding = 'path.command'): array
    {
        $base = sys_get_temp_dir() . '/omegacmd-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $save = $base . '/save';
        mkdir($save, 0777, true);

        $app = new Application($base);
        $app->set($binding, $save);

        return [$app, $save];
    }

    public function testCreatesACommandFileForMakeCommand(): void
    {
        [$app, $save] = $this->makeCommandApp();

        $command = new MakeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Command', $result->getDisplay());
        $this->assertStringContainsString('created successfully', $result->getDisplay());
        $this->assertFileExists($save . '/UserProfileCommand.php');

        $app->flush();
    }

    public function testCreatesAControllerFileForMakeController(): void
    {
        [$app, $save] = $this->makeCommandApp('path.controller');

        $command = new MakeControllerCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertFileExists($save . '/UserProfileController.php');

        $app->flush();
    }

    public function testCreatesAnExceptionFileForMakeException(): void
    {
        [$app, $save] = $this->makeCommandApp('path.exception');

        $command = new MakeExceptionCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertFileExists($save . '/UserProfileException.php');

        $app->flush();
    }

    public function testCreatesAMiddlewareFileForMakeMiddleware(): void
    {
        [$app, $save] = $this->makeCommandApp('path.middleware');

        $command = new MakeMiddlewareCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertFileExists($save . '/UserProfileMiddleware.php');

        $app->flush();
    }

    public function testCreatesAProviderFileForMakeProvider(): void
    {
        [$app, $save] = $this->makeCommandApp('path.provider');

        $command = new MakeProviderCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('ServiceProvider', $result->getDisplay());
        $this->assertFileExists($save . '/UserProfileProvider.php');

        $app->flush();
    }

    public function testCreatesAViewFileForMakeView(): void
    {
        [$app, $save] = $this->makeCommandApp('path.view');

        $command = new MakeViewCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('view', $result->getDisplay());
        $this->assertFileExists($save . '/UserProfile.template.php');

        $app->flush();
    }

    public function testReportsThatTheFileAlreadyExistsForMakeCommand(): void
    {
        [$app, $save] = $this->makeCommandApp();
        file_put_contents($save . '/UserCommand.php', 'x');

        $command = new MakeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('already exists', $result->getDisplay());

        $app->flush();
    }

    public function testRejectsANonStringNameArgument(): void
    {
        [$app] = $this->makeCommandApp();

        $command = new MakeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 42]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "name" argument must be a string.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenTheMakeAttributeIsMissing(): void
    {
        [$app] = $this->makeCommandApp();

        $command = new NoMakeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Missing #[Make] attribute.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenThePathBindingIsNotAString(): void
    {
        [$app] = $this->makeCommandApp();
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
        $base = sys_get_temp_dir() . '/omegacmd-' . bin2hex(random_bytes(4));
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

    public function testReplacesKebabPatternVariables(): void
    {
        [$app, $save] = $this->makeCommandApp();

        $command = new MakeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserAdminExport']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);

        $content = (string) file_get_contents($save . '/UserAdminExportCommand.php');
        $this->assertStringContainsString('user-admin-export', $content);

        $app->flush();
    }
}
