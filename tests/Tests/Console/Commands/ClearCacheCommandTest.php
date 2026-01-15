<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Cache\CacheFactory;
use Omega\Cache\Exceptions\UnknownStorageException;
use Omega\Cache\Storage\File;
use Omega\Cache\Storage\Memory;
use Omega\Console\Commands\ClearCacheCommand;
use Omega\Container\Exceptions\CircularAliasException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function ob_get_clean;
use function ob_start;

#[CoversClass(Application::class)]
#[CoversClass(CacheFactory::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(ClearCacheCommand::class)]
#[CoversClass(Memory::class)]
#[CoversClass(UnknownStorageException::class)]
class ClearCacheCommandTest extends TestCase
{
    private ?Application $app = null;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->app = new Application(__DIR__);
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
    protected function teardown(): void
    {
        $this->app->flush();
        $this->app = null;
    }

    /**
     * Test it can run command.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function testItCanRunCommand(): void
    {
        $command = new ClearCacheCommand(['omega', 'clear:cache']);

        ob_start();
        $code = $command->clear($this->app);
        $out  = ob_get_clean();

        $this->assertEquals(1, $code);
        $this->assertStringContainsString('Cache is not set yet.', $out);
    }

    /**
     * Test it can clear default driver.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function testItCanClearDefaultDriver(): void
    {
        $this->app->set('cache', fn () => new CacheFactory('file', new File([
            'ttl'  => 3_600,
            'path' => get_path('path.cache')
        ])));
        $command = new ClearCacheCommand(['omega', 'clear:cache']);

        ob_start();
        $code = $command->clear($this->app);
        $out  = ob_get_clean();

        $this->assertEquals(0, $code);
        $this->assertStringContainsString('Done default cache driver has been clear.', $out);
    }

    /**
     * Test it can all driver.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function testItCanAllDriver(): void
    {
        $cacheManager = new CacheFactory('memory', new Memory(['ttl' => 3_600]));
        $this->app->set('cache', fn () => $cacheManager);
        $command = new ClearCacheCommand(['omega', 'clear:cache', '--all'], ['all' => true]);

        ob_start();
        $code = $command->clear($this->app);
        $out  = ob_get_clean();

        $this->assertEquals(0, $code);
        $this->assertStringContainsString("Clear 'memory' driver.", $out);
    }

    /**
     * Test it can be specific driver.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws UnknownStorageException if a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function testItCanSpecificDriver(): void
    {
        $cacheManager = new CacheFactory('memory', new Memory(['ttl' => 3_600]));
        $this->app->set('cache', fn () => $cacheManager);
        $command = new ClearCacheCommand(['omega', 'clear:cache', '--drivers memory'], ['drivers' => 'memory']);

        ob_start();
        $code = $command->clear($this->app);
        $out  = ob_get_clean();

        $this->assertEquals(0, $code);
        $this->assertStringContainsString("Clear 'memory' driver.", $out);
    }
}
