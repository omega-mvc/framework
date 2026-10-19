<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MakeMiddlewareCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

use const DIRECTORY_SEPARATOR;

#[CoversClass(MakeMiddlewareCommand::class)]
final class MakeMiddlewareCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: string}
     */
    private function makeMiddlewareFileApp(): array
    {
        $base = sys_get_temp_dir() . '/omegamkc-' . bin2hex(random_bytes(4));

        $save = $base . '/save';
        mkdir($save, 0777, true);

        $app = new Application($base);
        $app->set('path.middleware', $save);

        return [$app, $save];
    }

    public function testCreatesAMiddlewareFileForMakeMiddleware(): void
    {
        [$app, $save] = $this->makeMiddlewareFileApp();

        $command = new MakeMiddlewareCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $expected = 'Middleware [app' . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR
            . 'UserProfileMiddleware.php] created successfully.';

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString($expected, $result->getDisplay());
        $this->assertFileExists($save . '/UserProfileMiddleware.php');

        $content = (string) file_get_contents($save . '/UserProfileMiddleware.php');
        $this->assertStringContainsString('class UserProfileMiddleware', $content);
        $this->assertStringContainsString('namespace App\Middlewares;', $content);
        $this->assertStringNotContainsString('#!', $content);

        $app->flush();
    }

    public function testReportsThatTheMiddlewareFileAlreadyExists(): void
    {
        [$app, $save] = $this->makeMiddlewareFileApp();
        file_put_contents($save . '/UserMiddleware.php', 'x');

        $command = new MakeMiddlewareCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('already exists.', $result->getDisplay());

        $app->flush();
    }

    public function testRejectsANonStringNameArgument(): void
    {
        [$app] = $this->makeMiddlewareFileApp();

        $command = new MakeMiddlewareCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 42]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "name" argument must be a string.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenThePathMiddlewareBindingIsNotAString(): void
    {
        [$app] = $this->makeMiddlewareFileApp();
        $app->set('path.middleware', 123);

        $command = new MakeMiddlewareCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "path.middleware" binding must resolve to a string path.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenTheTargetDirectoryCannotBeCreated(): void
    {
        $base = sys_get_temp_dir() . '/omegamkc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $blocker = $base . 'blocker';
        touch($blocker);
        $app->set('path.middleware', $blocker);

        $command = new MakeMiddlewareCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run(['name' => 'User']);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Unable to create directory', $result->getDisplay());

        $app->flush();
    }
}
