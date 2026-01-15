<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Container\Exceptions\CircularAliasException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Application\Application;
use Omega\Text\Str;

use function explode;

#[CoversClass(Application::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Str::class)]
abstract class AbstractTestCommand extends TestCase
{
    protected ?Application $app;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    protected function setUp(): void
    {
        $this->app = new Application(basePath: __DIR__);

        $this->app->set('path.view', __DIR__ . slash(path: '/fixtures/'));
        $this->app->set('path.controller', __DIR__ . slash(path: '/fixtures/'));
        $this->app->set('path.services', __DIR__ . slash(path: '/fixtures/'));
        $this->app->set('path.model', slash(path: '/fixtures/'));
        $this->app->set('path.command', __DIR__ . slash(path: '/fixtures/'));
        $this->app->set('path.config', __DIR__ . slash(path: '/fixtures/'));
        $this->app->set('path.migration', __DIR__ . slash(path: '/fixtures/migration/'));
        $this->app->set('path.seeder', __DIR__ . slash('/fixtures/seeders/'));
        $this->app->set('path.storage', __DIR__ . slash(path: '/fixtures/storage/'));
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->app->flush();
        $this->app = null;
    }

    /**
     * @return string[]
     */
    protected function argv(string $argv): array
    {
        return explode(' ', $argv);
    }

    protected function assertSuccess(int $code): void
    {
        Assert::assertEquals(0, $code, 'Command exit with success code');
    }

    protected function assertFails(int $code): void
    {
        Assert::assertGreaterThan(0, $code, 'Command exit with fail code');
    }

    public function assertContain(string $contain, string $in): void
    {
        Assert::assertTrue(Str::contains($in, $contain), "This " . $contain . " is contained in " . $in);
    }
}
