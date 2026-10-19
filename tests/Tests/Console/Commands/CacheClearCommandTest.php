<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Cache\CacheManager;
use Omega\Console\Commands\CacheClearCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\FakeCache;
use Tests\TestCase;

#[CoversClass(CacheClearCommand::class)]
final class CacheClearCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: CacheManager}
     */
    private function cacheClearApp(): array
    {
        $base = sys_get_temp_dir() . '/omegacc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app   = new Application($base);
        $cache = new CacheManager('file', new FakeCache());
        $app->set('cache', $cache);

        return [$app, $cache];
    }

    public function testFailsWhenTheCacheManagerIsNotBound(): void
    {
        $base = sys_get_temp_dir() . '/omegacc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Cache is not set yet.', $result->getDisplay());

        $app->flush();
    }

    public function testClearsTheDefaultCacheDriver(): void
    {
        [$app] = $this->cacheClearApp();

        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString("Cleared 'file' driver.", $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenTheDefaultDriverCannotBeCleared(): void
    {
        [$app] = $this->cacheClearApp();
        $app->set('cache', new CacheManager('file', new FakeCache()->setClearResult(false)));

        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("Failed to clear 'file' driver.", $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenTheDefaultDriverThrows(): void
    {
        [$app] = $this->cacheClearApp();
        $app->set('cache', new CacheManager('file', new FakeCache()->setClearException(new RuntimeException('boom'))));

        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("Failed to clear 'file' driver: boom", $result->getDisplay());

        $app->flush();
    }

    public function testClearsAllRegisteredDriversWithAll(): void
    {
        [$app, $cache] = $this->cacheClearApp();
        $cache->setDriver('redis', new FakeCache());

        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--all' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString("Cleared 'file' driver.", $result->getDisplay());
        $this->assertStringContainsString("Cleared 'redis' driver.", $result->getDisplay());

        $app->flush();
    }

    public function testWarnsWhenAllOverridesDrivers(): void
    {
        [$app, $cache] = $this->cacheClearApp();
        $cache->setDriver('redis', new FakeCache());

        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--all' => true, '--drivers' => ['file']]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString("'--all' overrides '--drivers': clearing all registered drivers.", $result->getDisplay());
        $this->assertStringContainsString("Cleared 'redis' driver.", $result->getDisplay());

        $app->flush();
    }

    public function testSkipsUnsupportedDriversWhenClearingSpecificDrivers(): void
    {
        [$app, $cache] = $this->cacheClearApp();
        $cache->setDriver('redis', new FakeCache()->setSupported(false));

        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--drivers' => ['file', 'redis']]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString("Cleared 'file' driver.", $result->getDisplay());
        $this->assertStringContainsString("Skipping 'redis' driver: not supported.", $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenASpecificDriverCannotBeCleared(): void
    {
        [$app, $cache] = $this->cacheClearApp();
        $cache->setDriver('redis', new FakeCache()->setClearResult(false));

        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--drivers' => ['redis']]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("Failed to clear 'redis' driver.", $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenASpecificDriverThrows(): void
    {
        [$app, $cache] = $this->cacheClearApp();
        $cache->setDriver('redis', new FakeCache()->setClearException(new RuntimeException('boom')));

        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--drivers' => ['redis']]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("Failed to clear 'redis': boom", $result->getDisplay());

        $app->flush();
    }

    public function testFailsForAnUnknownDriverName(): void
    {
        [$app] = $this->cacheClearApp();

        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--drivers' => ['nope']]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("Failed to clear 'nope':", $result->getDisplay());

        $app->flush();
    }

    public function testFallsBackToTheDefaultDriverWhenOnlyEmptyNamesAreGiven(): void
    {
        [$app] = $this->cacheClearApp();

        $command = new CacheClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--drivers' => ['']]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString("Cleared 'file' driver.", $result->getDisplay());

        $app->flush();
    }
}
