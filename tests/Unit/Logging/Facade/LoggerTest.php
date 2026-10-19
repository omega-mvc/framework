<?php

declare(strict_types=1);

namespace Tests\Logging\Facade;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Facade\AbstractFacade;
use Omega\Facade\Exceptions\FacadeObjectNotSetException;
use Omega\Logging\Facade\Logger;
use Omega\Logging\LoggingServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LogLevel;
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

#[CoversClass(Logger::class)]
final class LoggerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/omega-log-facade-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        AbstractFacade::setFacadeBase(null);
        AbstractFacade::flushInstance();

        if (isset($this->app)) {
            $this->app->flush();
        }

        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testReturnsTheFacadeAccessor(): void
    {
        $this->assertSame('logging', Logger::getFacadeAccessor());
    }

    public function testForwardsStaticCallsToTheLoggingManager(): void
    {
        $app = $this->makeApp($this->tempDir);
        $this->app = $app;
        AbstractFacade::setFacadeBase($app);

        Logger::info('hello facade');

        $this->assertStringContainsString(
            'hello facade',
            (string) file_get_contents($this->tempDir . '/omega.log')
        );
    }

    public function testThrowsAnExceptionForAStaticCallWithoutAnApplication(): void
    {
        $this->expectException(FacadeObjectNotSetException::class);

        Logger::info('no application');
    }

    private function makeApp(string $tempDir): Application
    {
        $app = new Application(slash(__DIR__ . '/../fixtures/support/'));
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
