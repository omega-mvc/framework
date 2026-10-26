<?php

declare(strict_types=1);

namespace Tests\Logging;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Logging\Exception\LogArgumentException;
use Omega\Logging\Exception\UnknownDriverException;
use Omega\Logging\LoggingManager;
use Omega\Logging\LoggingServiceProvider;
use Omega\Logging\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Tests\TestCase;

use function array_diff;
use function array_map;
use function file_get_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function Omega\Application\slash;

#[CoversClass(LoggingServiceProvider::class)]
final class LoggingServiceProviderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/omega-log-provider-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->app)) {
            $this->app->flush();
        }

        $this->removeProviderDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testRegistersALoggingManager(): void
    {
        $app = $this->makeApp($this->tempDir, $this->driverConfig($this->tempDir));
        $this->app = $app;

        $logging = $app->get('logging');

        $this->assertInstanceOf(LoggingManager::class, $logging);
        $this->assertInstanceOf(LoggerInterface::class, $logging);
    }

    public function testRegistersEveryConfiguredDriver(): void
    {
        $app = $this->makeApp($this->tempDir, $this->driverConfig($this->tempDir));
        $this->app = $app;
        $logging = $app->get('logging');
        $this->assertInstanceOf(LoggingManager::class, $logging);

        $this->assertInstanceOf(Stream::class, $logging->getDriver('stream'));
        $this->assertInstanceOf(Stream::class, $logging->getDriver('custom'));
    }

    public function testWritesLogEntriesWithTheDefaultDriver(): void
    {
        $app = $this->makeApp($this->tempDir, $this->driverConfig($this->tempDir));
        $this->app = $app;

        $logging = $app->get('logging');
        $this->assertInstanceOf(LoggingManager::class, $logging);

        $logging->log(LogLevel::INFO, 'via provider');

        $this->assertStringContainsString(
            'via provider',
            (string) file_get_contents($this->tempDir . '/omega.log')
        );
    }

    public function testThrowsAnExceptionForAnUnsupportedDriverType(): void
    {
        $config = $this->driverConfig($this->tempDir);
        $this->assertIsArray($config['logging']);
        $config['logging']['bogus'] = ['type' => 'unsupported'];

        $app = $this->makeApp($this->tempDir, $config);
        $this->app = $app;

        $this->expectException(LogArgumentException::class);
        $this->expectExceptionMessage('Unsupported logger type [unsupported].');

        $app->get('logging.bogus');
    }

    public function testThrowsAnExceptionWhenTheConfigServiceIsNotArrayLike(): void
    {
        $app = new Application(slash(__DIR__ . '/fixtures/support/'));
        $app->set('config', static fn () => new \stdClass());
        $this->app = $app;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The config service must be an array or implement ArrayAccess.');

        (new LoggingServiceProvider($app))->boot();
    }

    public function testThrowsAnExceptionWhenTheLoggingConfigurationIsNotAnArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The default logging driver must be a string.');

        $this->app = $this->makeApp($this->tempDir, ['logging' => 'not an array']);
    }

    public function testThrowsAnExceptionWhenTheDefaultDriverIsMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The default logging driver must be a string.');

        $this->app = $this->makeApp($this->tempDir, ['logging' => []]);
    }

    public function testSkipsDriversWithNonArrayOptions(): void
    {
        $config = $this->driverConfig($this->tempDir);
        $this->assertIsArray($config['logging']);
        $config['logging']['bogus'] = 'not an array';

        $app = $this->makeApp($this->tempDir, $config);
        $this->app = $app;

        $logging = $app->get('logging');
        $this->assertInstanceOf(LoggingManager::class, $logging);

        $this->expectException(UnknownDriverException::class);

        $logging->getDriver('bogus');
    }

    public function testThrowsAnExceptionWhenTheDefaultDriverIsNotALogger(): void
    {
        $app = $this->makeApp($this->tempDir, $this->driverConfig($this->tempDir));
        $this->app = $app;

        $app->set('logging.stream', static fn () => new \stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The default logging driver is not a logger.');

        $app->get('logging');
    }

    public function testThrowsAnExceptionForAnInvalidStreamConfiguration(): void
    {
        $config = $this->driverConfig($this->tempDir);
        $this->assertIsArray($config['logging']);
        $this->assertIsArray($config['logging']['stream']);
        $config['logging']['stream']['path'] = 123;

        $app = $this->makeApp($this->tempDir, $config);
        $this->app = $app;

        $this->expectException(LogArgumentException::class);
        $this->expectExceptionMessage('Unable to create the stream logger: invalid configuration.');

        $app->get('logging.stream');
    }

    /**
     * Build a logging configuration for the provider tests.
     *
     * @param string $tempDir The temporary directory for the log files.
     * @return array<string, mixed>
     */
    private function driverConfig(string $tempDir): array
    {
        return [
            'logging' => [
                'default' => 'stream',
                'stream'  => [
                    'type'    => 'stream',
                    'path'    => $tempDir . '/omega.log',
                    'minimum' => LogLevel::DEBUG,
                    'options' => ['appendContext' => true],
                ],
                'custom'  => [
                    'type'    => 'stream',
                    'path'    => $tempDir . '/custom.log',
                    'minimum' => LogLevel::WARNING,
                    'options' => [],
                ],
            ],
        ];
    }

    /**
     * Build an application with the logging provider booted.
     *
     * @param string               $tempDir The temporary directory for the log files.
     * @param array<string, mixed> $config  The application configuration.
     * @return Application The configured application instance.
     */
    private function makeApp(string $tempDir, array $config): Application
    {
        $app = new Application(slash(__DIR__ . '/fixtures/support/'));
        $app->set('config', static fn () => new ConfigRepository($config));

        (new LoggingServiceProvider($app))->boot();

        return $app;
    }

    private function removeProviderDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        array_map(
            function (string $entry) use ($dir): void {
                $path = $dir . slash(path: '/') . $entry;

                if (is_dir($path)) {
                    $this->removeProviderDirectory($path);
                } else {
                    @unlink($path);
                }
            },
            array_diff(scandir($dir), ['.', '..'])
        );

        @rmdir($dir);
    }
}
