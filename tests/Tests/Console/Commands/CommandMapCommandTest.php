<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\CommandMapCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(CommandMapCommand::class)]
final class CommandMapCommandTest extends TestCase
{
    public function testCachesTheDiscoveredCommandMap(): void
    {
        $base = sys_get_temp_dir() . '/omegacm-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $cachePath = $app->getApplicationCachePath() . 'commands.php';
        mkdir($app->getApplicationCachePath(), 0777, true);

        $command = new CommandMapCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString("Command map successfully cached at: {$cachePath}", $result->getDisplay());
        $this->assertFileExists($cachePath);

        $content = (string) file_get_contents($cachePath);
        $this->assertStringContainsString('use Omega\Console\Commands\MakeCommand;', $content);
        $this->assertStringContainsString("'make:command' => MakeCommand::class,", $content);
        $this->assertStringContainsString('return [', $content);

        $app->flush();
    }

    public function testAbortsWhenCommandShortNamesCollide(): void
    {
        $base = sys_get_temp_dir() . '/omegacm-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        require_once __DIR__ . '/Fixtures/MakeCommand.php';
        $app->set('path.command', __DIR__ . '/Fixtures');

        $command = new CommandMapCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("short-name collision on 'MakeCommand'", $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenTheCommandsCannotBeDiscovered(): void
    {
        $base = sys_get_temp_dir() . '/omegacm-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        unset($app['path.command']);

        $command = new CommandMapCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Failed to discover console commands:', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenWritingTheCacheThrows(): void
    {
        $app = new Application(sys_get_temp_dir() . '/omegacm-' . bin2hex(random_bytes(4)) . "\0");

        $command = new CommandMapCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Failed to write cache file: ', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenTheCacheFileCannotBeWritten(): void
    {
        $base = sys_get_temp_dir() . '/omegacm-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $cachePath = $app->getApplicationCachePath() . 'commands.php';
        mkdir(dirname(dirname($cachePath)), 0777, true);
        touch(dirname($cachePath));

        $command = new CommandMapCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run([]);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("Failed to write cache file at: {$cachePath}", $result->getDisplay());

        $app->flush();
    }
}
