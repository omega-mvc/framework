<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Console\Commands;

use Exception;
use Omega\Application\Application;
use Omega\Cache\CacheManager;
use Omega\Cache\Exceptions\UnknownStorageException;
use Omega\Cache\Storage\File;
use Omega\Cache\Storage\Memory;
use Omega\Console\Commands\ClearCacheCommand;
use Omega\Container\Exceptions\CircularAliasException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

use function ob_get_clean;
use function ob_start;

/**
 * Test suite for the ClearCache console command.
 *
 * This test class verifies the behavior of the `clear:cache` console command
 * under different execution scenarios. It ensures that the command correctly
 * handles cases where no cache is configured, clears the default cache driver,
 * clears all registered cache drivers, and clears one or more specific drivers
 * when explicitly requested.
 *
 * The tests rely on a real `Application` instance initialized with filesystem
 * fixtures to simulate a realistic runtime environment. Output buffering is
 * used to capture and assert console messages, while return codes are checked
 * to validate command execution status.
 *
 * The application state is properly flushed and reset after each test to
 * guarantee isolation and avoid side effects between test cases.
 *
 * @category   Tests
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(CacheManager::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(ClearCacheCommand::class)]
#[CoversClass(Memory::class)]
#[CoversClass(UnknownStorageException::class)]
final class ClearCacheCommandTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * The application instance used for running console command tests.
     *
     * This property holds an instance of `Omega\Application\Application` initialized
     * in the `setUp()` method before each test. It provides access to paths, services,
     * and configuration needed by the commands under test. It is reset to `null` in
     * `tearDown()` to avoid side effects between tests.
     *
     * @var Application|null
     */
    private ?Application $app = null;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    protected function setUp(): void
    {
        $this->app = new Application($this->setFixtureBasePath() . slash(path: '/fixtures/application-write'));
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
        $this->app->set('cache', fn () => new CacheManager('file', new File([
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
        $cacheManager = new CacheManager('memory', new Memory(['ttl' => 3_600]));
        $this->app->set('cache', fn () => $cacheManager);
        $command = new ClearCacheCommand(['omega', 'clear:cache', '--all'], ['all' => true]);

        ob_start();
        $code = $command->clear($this->app);
        $out  = ob_get_clean();

        $this->assertEquals(0, $code);
        $this->assertStringContainsString("Cleared 'memory' driver.", $out);
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
        $cacheManager = new CacheManager('memory', new Memory(['ttl' => 3_600]));
        $this->app->set('cache', fn () => $cacheManager);
        $command = new ClearCacheCommand(['omega', 'clear:cache', '--drivers memory'], ['drivers' => 'memory']);

        ob_start();
        $code = $command->clear($this->app);
        $out  = ob_get_clean();

        $this->assertEquals(0, $code);
        $this->assertStringContainsString("Cleared 'memory' driver.", $out);
    }
}
