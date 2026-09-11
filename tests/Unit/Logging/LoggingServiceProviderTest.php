<?php

declare(strict_types=1);

namespace Tests\Logging;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Logging\Exception\LogArgumentException;
use Omega\Logging\LoggingManager;
use Omega\Logging\LoggingServiceProvider;
use Omega\Logging\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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

covers(LoggingServiceProvider::class);

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir() . '/omega-log-provider-' . uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    if (isset($this->app)) {
        $this->app->flush();
    }

    removeProviderDirectory($this->tempDir);
});

it('registers a logging manager', function (): void {
    $app = makeApp($this->tempDir, driverConfig($this->tempDir));
    $this->app = $app;

    $logging = $app->get('logging');

    expect($logging)->toBeInstanceOf(LoggingManager::class);
    expect($logging)->toBeInstanceOf(LoggerInterface::class);
});

it('registers every configured driver', function (): void {
    $app = makeApp($this->tempDir, driverConfig($this->tempDir));
    $this->app = $app;
    $logging = $app->get('logging');
    $this->assertInstanceOf(LoggingManager::class, $logging);

    expect($logging->getDriver('stream'))->toBeInstanceOf(Stream::class);
    expect($logging->getDriver('custom'))->toBeInstanceOf(Stream::class);
});

it('writes log entries with the default driver', function (): void {
    $app = makeApp($this->tempDir, driverConfig($this->tempDir));
    $this->app = $app;

    $logging = $app->get('logging');
    $this->assertInstanceOf(LoggingManager::class, $logging);

    $logging->log(LogLevel::INFO, 'via provider');

    expect(file_get_contents($this->tempDir . '/omega.log'))->toContain('via provider');
});

it('throws an exception for an unsupported driver type', function (): void {
    $config = driverConfig($this->tempDir);
    $this->assertIsArray($config['logging']);
    $config['logging']['bogus'] = ['type' => 'unsupported'];

    $app = makeApp($this->tempDir, $config);
    $this->app = $app;

    $app->get('logging.bogus');
})->throws(LogArgumentException::class, 'Unsupported logger type [unsupported].');

/**
 * Build a logging configuration for the provider tests.
 *
 * @param string $tempDir The temporary directory for the log files.
 * @return array<string, mixed>
 */
function driverConfig(string $tempDir): array
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
function makeApp(string $tempDir, array $config): Application
{
    $app = new Application(slash(__DIR__ . '/../fixtures/support/'));
    $app->set('config', static fn () => new ConfigRepository($config));

    (new LoggingServiceProvider($app))->boot();

    return $app;
}

function removeProviderDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    array_map(
        function (string $entry) use ($dir): void {
            $path = $dir . slash(path: '/') . $entry;

            if (is_dir($path)) {
                removeProviderDirectory($path);
            } else {
                @unlink($path);
            }
        },
        array_diff(scandir($dir), ['.', '..'])
    );

    @rmdir($dir);
}
