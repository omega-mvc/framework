<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\CommandClearCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(CommandClearCommand::class)]
final class CommandClearCommandTest extends TestCase
{
    public function testReportsWhenThereIsNoConsoleCacheFile(): void
    {
        $base = sys_get_temp_dir() . '/omegacl-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $command = new CommandClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('No console cache file found. Nothing to clear.', $result->getDisplay());

        $app->flush();
    }

    public function testClearsAnExistingConsoleCacheFile(): void
    {
        $base = sys_get_temp_dir() . '/omegacl-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $cachePath = $app->getApplicationCachePath() . 'commands.php';
        mkdir($app->getApplicationCachePath(), 0777, true);
        file_put_contents($cachePath, '<?php return [];');

        $this->assertFileExists($cachePath);

        $command = new CommandClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Console command cache cleared successfully.', $result->getDisplay());
        $this->assertStringContainsString('The application will now use dynamic discovery.', $result->getDisplay());
        $this->assertFileDoesNotExist($cachePath);

        $app->flush();
    }

    public function testFailsWhenTheConsoleCacheFileCannotBeDeleted(): void
    {
        $base = sys_get_temp_dir() . '/omegacl-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $cachePath = $app->getApplicationCachePath() . 'commands.php';
        mkdir(dirname($cachePath), 0777, true);
        mkdir($cachePath);

        $command = new CommandClearCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run([]);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("Failed to delete the cache file at: {$cachePath}", $result->getDisplay());

        $app->flush();
    }
}
