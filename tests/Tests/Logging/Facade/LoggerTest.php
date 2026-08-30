<?php

/**
 * Part of Omega - Tests\Logging Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Logging\Facade;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Facade\AbstractFacade;
use Omega\Facade\Exceptions\FacadeObjectNotSetException;
use Omega\Logging\Facade\Logger;
use Omega\Logging\LoggingServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Tests\FixturesPathTrait;

use function array_diff;
use function array_map;
use function file_get_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function str_contains;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function Omega\Application\slash;

/**
 * Class LoggerTest
 *
 * This test suite verifies the behavior of the {@see Logger} facade:
 * its container accessor and the static forwarding of logging calls.
 *
 * @category   Tests
 * @package    Logging
 * @subpackage Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Logger::class)]
final class LoggerTest extends TestCase
{
    use FixturesPathTrait;

    /** @var string Temporary directory used to isolate log file operations. */
    private string $tempDir;

    /** @var Application|null The application created during a test. */
    private ?Application $app = null;

    /**
     * Sets up the environment before each test method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/omega-log-facade-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    /**
     * Tears down the environment after each test method.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        AbstractFacade::setFacadeBase(null);
        AbstractFacade::flushInstance();

        $this->app?->flush();

        $this->removeDirectory($this->tempDir);
    }

    /**
     * Test the facade accessor.
     *
     * @return void
     */
    public function testGetFacadeAccessor(): void
    {
        $this->assertSame('logging', Logger::getFacadeAccessor());
    }

    /**
     * Test a static call forwards to the logging manager.
     *
     * @return void
     */
    public function testStaticCallForwardsToLoggingManager(): void
    {
        $app = $this->makeApp();
        AbstractFacade::setFacadeBase($app);

        Logger::info('hello facade');

        $this->assertTrue(str_contains(file_get_contents($this->tempDir . '/omega.log'), 'hello facade'));
    }

    /**
     * Test a static call without an application throws.
     *
     * @return void
     */
    public function testStaticCallWithoutApplicationThrows(): void
    {
        try {
            Logger::info('no application');
            $this->fail('Expected FacadeObjectNotSetException was not thrown');
        } catch (FacadeObjectNotSetException $e) {
            $this->assertInstanceOf(FacadeObjectNotSetException::class, $e);
        }
    }

    /**
     * Build an application with the logging provider booted.
     *
     * @return Application
     */
    private function makeApp(): Application
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));
        $app->set('config', fn () => new ConfigRepository([
            'logging' => [
                'default' => 'stream',
                'stream'  => [
                    'type'    => 'stream',
                    'path'    => $this->tempDir . '/omega.log',
                    'minimum' => LogLevel::DEBUG,
                    'options' => ['appendContext' => true],
                ],
            ],
        ]));

        (new LoggingServiceProvider($app))->boot();

        return $this->app = $app;
    }

    /**
     * Recursively remove a directory.
     *
     * @param string $dir The directory to remove.
     * @return void
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        array_map(
            function (string $entry) use ($dir): void {
                $path = $dir . slash(path: '/') . $entry;

                if (is_dir($path)) {
                    $this->removeDirectory($path);
                } else {
                    @unlink($path);
                }
            },
            array_diff(scandir($dir), ['.', '..'])
        );

        @rmdir($dir);
    }
}
