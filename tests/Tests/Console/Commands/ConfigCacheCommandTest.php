<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\ConfigCacheCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(ConfigCacheCommand::class)]
final class ConfigCacheCommandTest extends TestCase
{
    public function testCachesTheConfiguration(): void
    {
        $base = sys_get_temp_dir() . '/omegacc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $cachePath = $app->getApplicationCachePath() . 'config.php';
        mkdir($app->getApplicationCachePath(), 0777, true);

        $command = new ConfigCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Configuration cached successfully.', $result->getDisplay());
        $this->assertFileExists($cachePath);

        $content = (string) file_get_contents($cachePath);
        $this->assertStringContainsString('<?php return array (', $content);

        $app->flush();
    }

    public function testFailsWhenTheConfigurationCacheFileCannotBeWritten(): void
    {
        $base = sys_get_temp_dir() . '/omegacc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $cachePath = $app->getApplicationCachePath() . 'config.php';
        mkdir(dirname(dirname($cachePath)), 0777, true);
        touch(dirname($cachePath));

        $command = new ConfigCacheCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run([]);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Failed to write the configuration cache file.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenAnInvalidCachedConfigurationIsPresent(): void
    {
        $base = sys_get_temp_dir() . '/omegacc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $cachePath = $app->getApplicationCachePath() . 'config.php';
        mkdir($app->getApplicationCachePath(), 0777, true);
        file_put_contents($cachePath, '<?php return "nope";');

        $command = new ConfigCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('An error occurred while caching configuration:', $result->getDisplay());
        $this->assertStringContainsString(
            'Invalid config cache file: expected array, got string',
            $result->getDisplay()
        );

        $app->flush();
    }
}
