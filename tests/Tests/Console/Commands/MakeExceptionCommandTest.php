<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MakeExceptionCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

use const DIRECTORY_SEPARATOR;

#[CoversClass(MakeExceptionCommand::class)]
final class MakeExceptionCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: string}
     */
    private function makeExceptionFileApp(): array
    {
        $base = sys_get_temp_dir() . '/omegamkc-' . bin2hex(random_bytes(4));

        $save = $base . '/save';
        mkdir($save, 0777, true);

        $app = new Application($base);
        $app->set('path.exception', $save);

        return [$app, $save];
    }

    public function testCreatesAnExceptionFileForMakeException(): void
    {
        [$app, $save] = $this->makeExceptionFileApp();

        $command = new MakeExceptionCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $expected = 'Exception [app' . DIRECTORY_SEPARATOR . 'Exceptions' . DIRECTORY_SEPARATOR
            . 'UserProfileException.php] created successfully.';

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString($expected, $result->getDisplay());
        $this->assertFileExists($save . '/UserProfileException.php');

        $content = (string) file_get_contents($save . '/UserProfileException.php');
        $this->assertStringContainsString('class UserProfileException extends ExceptionHandler', $content);
        $this->assertStringContainsString('namespace App\Exceptions;', $content);
        $this->assertStringNotContainsString('#!', $content);

        $app->flush();
    }

    public function testReportsThatTheExceptionFileAlreadyExists(): void
    {
        [$app, $save] = $this->makeExceptionFileApp();
        file_put_contents($save . '/UserException.php', 'x');

        $command = new MakeExceptionCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('already exists.', $result->getDisplay());

        $app->flush();
    }

    public function testRejectsANonStringNameArgument(): void
    {
        [$app] = $this->makeExceptionFileApp();

        $command = new MakeExceptionCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 42]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "name" argument must be a string.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenThePathExceptionBindingIsNotAString(): void
    {
        [$app] = $this->makeExceptionFileApp();
        $app->set('path.exception', 123);

        $command = new MakeExceptionCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.exception" binding must resolve to a string path.',
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
        $app->set('path.exception', $blocker);

        $command = new MakeExceptionCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run(['name' => 'User']);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Unable to create directory', $result->getDisplay());

        $app->flush();
    }
}
