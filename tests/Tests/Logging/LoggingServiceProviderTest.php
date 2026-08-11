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

namespace Tests\Logging;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Logging\Exception\LogArgumentException;
use Omega\Logging\LoggingManager;
use Omega\Logging\LoggingServiceProvider;
use Omega\Logging\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tests\FixturesPathTrait;

use function array_diff;
use function array_map;
use function file_exists;
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
 * Class LoggingServiceProviderTest
 *
 * This test suite verifies that the {@see LoggingServiceProvider} registers
 * every configured log driver in the container and exposes a
 * {@see LoggingManager} as the default `logging` service.
 *
 * @category   Tests
 * @package    Logging
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(LoggingServiceProvider::class)]
final class LoggingServiceProviderTest extends TestCase
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
        $this->tempDir = sys_get_temp_dir() . '/omega-log-provider-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    /**
     * Tears down the environment after each test method.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->app?->flush();

        $this->removeDirectory($this->tempDir);
    }

    /**
     * Test the provider registers a logging manager.
     *
     * @return void
     */
    public function testBootRegistersLoggingManager(): void
    {
        $app = $this->makeApp($this->driverConfig());

        $logging = $app->get('logging');

        $this->assertInstanceOf(LoggingManager::class, $logging);
        $this->assertInstanceOf(LoggerInterface::class, $logging);
    }

    /**
     * Test every configured driver is registered.
     *
     * @return void
     */
    public function testEveryConfiguredDriverIsRegistered(): void
    {
        $app = $this->makeApp($this->driverConfig());
        $logging = $app->get('logging');

        $this->assertInstanceOf(Stream::class, $logging->getDriver('stream'));
        $this->assertInstanceOf(Stream::class, $logging->getDriver('custom'));
    }

    /**
     * Test the default driver writes log entries.
     *
     * @return void
     */
    public function testDefaultDriverWritesLogEntries(): void
    {
        $app = $this->makeApp($this->driverConfig());

        $app->get('logging')->log(LogLevel::INFO, 'via provider');

        $this->assertTrue(str_contains(file_get_contents($this->tempDir . '/omega.log'), 'via provider'));
    }

    /**
     * Test an unsupported driver type throws.
     *
     * @return void
     */
    public function testUnsupportedDriverTypeThrows(): void
    {
        $config = $this->driverConfig();
        $config['logging']['bogus'] = ['type' => 'unsupported'];

        $app = $this->makeApp($config);

        $this->expectException(LogArgumentException::class);
        $this->expectExceptionMessage('Unsupported logger type [unsupported].');

        $app->get('logging.bogus');
    }

    /**
     * Build a driver configuration array.
     *
     * @return array<string, mixed>
     */
    private function driverConfig(): array
    {
        return [
            'logging' => [
                'default' => 'stream',
                'stream'  => [
                    'type'    => 'stream',
                    'path'    => $this->tempDir . '/omega.log',
                    'minimum' => LogLevel::DEBUG,
                    'options' => ['appendContext' => true],
                ],
                'custom'  => [
                    'type'    => 'stream',
                    'path'    => $this->tempDir . '/custom.log',
                    'minimum' => LogLevel::WARNING,
                    'options' => [],
                ],
            ],
        ];
    }

    /**
     * Build an application with the logging config and boot the provider.
     *
     * @param array<string, mixed> $config The full configuration array.
     * @return Application
     */
    private function makeApp(array $config): Application
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));
        $app->set('config', static fn () => new ConfigRepository($config));

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
