<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Console\Commands\VendorImportCommand;
use Omega\Container\Provider\AbstractServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_exists;
use function microtime;
use function ob_get_clean;
use function ob_start;
use function Omega\Time\now;
use function unlink;

#[CoversClass(AbstractServiceProvider::class)]
#[CoversClass(VendorImportCommand::class)]
final class VendorImportCommandsTest extends TestCase
{
    private ?string $basePath;

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
        $this->basePath = dirname(__FILE__);
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
        AbstractServiceProvider::flushModule();

        @unlink($this->basePath . slash(path: '/fixtures/copy/to/file.txt'));
    }

    /**
     * Test it can import.
     *
     * @return void
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    public function testItCanImport(): void
    {
        $publish = new VendorImportCommand(['omega', 'vendor:import', '--tag=test'], [
            'force' => false,
        ]);

        $random = now()->format('YmdHis') . microtime();

        AbstractServiceProvider::export(
            path: [$this->basePath . slash(path: '/fixtures/copy/from/file.txt') => $this->basePath . slash(path: '/fixtures/copy/to/file.txt')],
            tag: 'test'
        );
        AbstractServiceProvider::export(
            path: [$this->basePath . slash(path: '/fixtures/copy/from/folder') => $this->basePath . slash(path: '/fixtures/copy/to/folders/folder-') . $random],
            tag: 'test'
        );

        ob_start();
        $exit = $publish->main();
        ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(file_exists($this->basePath . slash(path: '/fixtures/copy/to/file.txt')));
        $this->assertTrue(file_exists($this->basePath . slash(path: '/fixtures/copy/to/folders/folder-') . $random . '/file.txt'));
    }

    /**
     * Test it can import with tag.
     *
     * @return void
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    public function testItCanImportWithTag(): void
    {
        $publish = new VendorImportCommand(['omega', 'vendor:import', '--tag=test'], [
            'force' => false,
            'tag'   => 'test',
        ]);

        $random = now()->format('YmdHis') . microtime();

        AbstractServiceProvider::export(
            path: [$this->basePath . slash(path: '/fixtures/copy/from/file.txt') => $this->basePath . slash(path: '/fixtures/copy/to/file.txt')],
            tag: 'test'
        );
        AbstractServiceProvider::export(
            path: [$this->basePath . slash(path: '/fixtures/copy/from/folder') => $this->basePath . slash(path: '/fixtures/copy/to/folders/folder-') . $random],
            tag: 'vendor'
        );

        ob_start();
        $exit = $publish->main();
        ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(file_exists($this->basePath . slash(path: '/fixtures/copy/to/file.txt')));
        $this->assertFalse(file_exists($this->basePath . slash(path: '/fixtures/copy/to/folders/folder-' . $random . '/file.txt')));
    }
}
