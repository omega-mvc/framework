<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MakeProviderCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

use const DIRECTORY_SEPARATOR;

#[CoversClass(MakeProviderCommand::class)]
final class MakeProviderCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: string}
     */
    private function makeProviderFileApp(): array
    {
        $base = sys_get_temp_dir() . '/omegamkc-' . bin2hex(random_bytes(4));

        $save = $base . '/save';
        mkdir($save, 0777, true);

        $app = new Application($base);
        $app->set('path.provider', $save);

        return [$app, $save];
    }

    public function testCreatesAProviderFileForMakeProvider(): void
    {
        [$app, $save] = $this->makeProviderFileApp();

        $command = new MakeProviderCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $expected = 'ServiceProvider [app' . DIRECTORY_SEPARATOR . 'Provider' . DIRECTORY_SEPARATOR
            . 'UserProfileProvider.php] created successfully.';

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString($expected, $result->getDisplay());
        $this->assertFileExists($save . '/UserProfileProvider.php');

        $content = (string) file_get_contents($save . '/UserProfileProvider.php');
        $this->assertStringContainsString('class UserProfileServiceProvider extends AbstractServiceProvider', $content);
        $this->assertStringContainsString('namespace App\\Providers;', $content);
        $this->assertStringNotContainsString('#!', $content);

        $app->flush();
    }

    public function testReportsThatTheProviderFileAlreadyExists(): void
    {
        [$app, $save] = $this->makeProviderFileApp();
        file_put_contents($save . '/UserProvider.php', 'x');

        $command = new MakeProviderCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('already exists.', $result->getDisplay());

        $app->flush();
    }

    public function testRejectsANonStringNameArgument(): void
    {
        [$app] = $this->makeProviderFileApp();

        $command = new MakeProviderCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 42]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "name" argument must be a string.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenThePathProviderBindingIsNotAString(): void
    {
        [$app] = $this->makeProviderFileApp();
        $app->set('path.provider', 123);

        $command = new MakeProviderCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'User']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "path.provider" binding must resolve to a string path.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenTheTargetDirectoryCannotBeCreated(): void
    {
        $base = sys_get_temp_dir() . '/omegamkc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $blocker = $base . 'blocker';
        touch($blocker);
        $app->set('path.provider', $blocker);

        $command = new MakeProviderCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run(['name' => 'User']);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Unable to create directory', $result->getDisplay());

        $app->flush();
    }
}
