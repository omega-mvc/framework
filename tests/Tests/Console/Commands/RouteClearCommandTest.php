<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\RouteClearCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(RouteClearCommand::class)]
final class RouteClearCommandTest extends TestCase
{
    public function testReportsWhenThereIsNoRouteCacheFile(): void
    {
        $base = sys_get_temp_dir() . '/omegarl-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new RouteClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('No route cache file found.', $result->getDisplay());

        $app->flush();
    }

    public function testClearsTheRouteCacheFile(): void
    {
        $base = sys_get_temp_dir() . '/omegarl-' . bin2hex(random_bytes(4));

        $app = new Application($base);

        mkdir($app->getApplicationCachePath(), 0777, true);
        file_put_contents($app->getApplicationCachePath() . 'route.php', '<?php return [];');

        $command = new RouteClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $cachePath = $app->getApplicationCachePath() . 'route.php';

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Route cache cleared successfully.', $result->getDisplay());
        $this->assertFileDoesNotExist($cachePath);

        $app->flush();
    }

    public function testReportsAnErrorWhenTheRouteCacheFileCannotBeRemoved(): void
    {
        $base = sys_get_temp_dir() . '/omegarl-' . bin2hex(random_bytes(4));
        $cachePath = $base . '/bootstrap/cache/route.php';
        mkdir($cachePath, 0777, true);

        $app = new Application($base);

        $command = new RouteClearCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run([]);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Unable to delete the route cache file.', $result->getDisplay());
        $this->assertFileExists($cachePath);

        $app->flush();
    }
}
