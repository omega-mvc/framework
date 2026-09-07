<?php

declare(strict_types=1);

namespace Tests\Logging\Facade;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Facade\AbstractFacade;
use Omega\Facade\Exceptions\FacadeObjectNotSetException;
use Omega\Logging\Facade\Logger;
use Omega\Logging\LoggingServiceProvider;
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

covers(Logger::class);

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir() . '/omega-log-facade-' . uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    AbstractFacade::setFacadeBase(null);
    AbstractFacade::flushInstance();

    if (isset($this->app) && $this->app) {
        $this->app->flush();
    }

    removeDirectory($this->tempDir);
});

it('returns the facade accessor', function (): void {
    expect(Logger::getFacadeAccessor())->toBe('logging');
});

it('forwards static calls to the logging manager', function (): void {
    $app = makeApp($this->tempDir);
    $this->app = $app;
    AbstractFacade::setFacadeBase($app);

    Logger::info('hello facade');

    expect(file_get_contents($this->tempDir . '/omega.log'))->toContain('hello facade');
});

it('throws an exception for a static call without an application', function (): void {
    Logger::info('no application');
})->throws(FacadeObjectNotSetException::class);

function makeApp(string $tempDir): Application
{
    $app = new Application(slash(__DIR__ . '/../../fixtures/support/'));
    $app->set('config', static fn () => new ConfigRepository([
        'logging' => [
            'default' => 'stream',
            'stream'  => [
                'type'    => 'stream',
                'path'    => $tempDir . '/omega.log',
                'minimum' => LogLevel::DEBUG,
                'options' => ['appendContext' => true],
            ],
        ],
    ]));

    (new LoggingServiceProvider($app))->boot();

    return $app;
}

function removeDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    array_map(
        function (string $entry) use ($dir): void {
            $path = $dir . slash(path: '/') . $entry;

            if (is_dir($path)) {
                removeDirectory($path);
            } else {
                @unlink($path);
            }
        },
        array_diff(scandir($dir), ['.', '..'])
    );

    @rmdir($dir);
}
