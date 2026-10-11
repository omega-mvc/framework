<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\ConfigClearCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(ConfigClearCommand::class)]
final class ConfigClearCommandTest extends TestCase
{
    public function testReportsWhenThereIsNoConfigurationCacheFile(): void
    {
        $base = sys_get_temp_dir() . '/omegacl-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $command = new ConfigClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('No configuration cache file found.', $result->getDisplay());

        $app->flush();
    }

    public function testClearsAnExistingConfigurationCacheFile(): void
    {
        $base = sys_get_temp_dir() . '/omegacl-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $cachePath = $app->getApplicationCachePath() . 'config.php';
        mkdir($app->getApplicationCachePath(), 0777, true);
        file_put_contents($cachePath, '<?php return [];');

        $this->assertFileExists($cachePath);

        $command = new ConfigClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Configuration cache cleared successfully.', $result->getDisplay());
        $this->assertFileDoesNotExist($cachePath);

        $app->flush();
    }

    public function testFailsWhenTheConfigurationCacheFileCannotBeRemoved(): void
    {
        $base = sys_get_temp_dir() . '/omegacl-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $cachePath = $app->getApplicationCachePath() . 'config.php';
        mkdir(dirname($cachePath), 0777, true);
        mkdir($cachePath);

        $command = new ConfigClearCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run([]);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Could not remove the configuration cache file.', $result->getDisplay());

        $app->flush();
    }
}
