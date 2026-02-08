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

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Console\Commands\VendorImportCommand;
use Omega\Container\Provider\AbstractServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

use function file_exists;
use function microtime;
use function ob_get_clean;
use function ob_start;
use function Omega\Time\now;
use function unlink;

/**
 * Test suite for the Vendor Import console command.
 *
 * This class verifies the behavior of the `VendorImportCommand`, which is
 * responsible for importing files and directories from vendor packages
 * into the application's structure based on optional tags.
 *
 * The tests simulate real file operations using fixture directories, ensuring
 * that files and folders are copied correctly according to the command's
 * logic. Output buffering is used to capture console messages, and file system
 * assertions validate the success or failure of import operations.
 *
 * Each test runs in isolation, and any temporary files created during the test
 * are cleaned up in the `tearDown()` method to prevent side effects.
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
#[CoversClass(AbstractServiceProvider::class)]
#[CoversClass(VendorImportCommand::class)]
final class VendorImportCommandsTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * The base path to the fixture application used for file operations.
     *
     * This property holds the root directory path of the test fixtures, providing
     * a controlled environment for simulating vendor file imports. It is
     * initialized in `setUp()` before each test and used throughout the test
     * methods to reference source and target paths for import operations.
     *
     * @var string|null
     */
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
        $this->basePath = $this->setFixtureBasePath();
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

        @unlink($this->basePath . slash(path: '/fixtures/application-write/copy/to/file.txt'));
    }

    /**
     * Test it can import.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanImport(): void
    {
        $publish = new VendorImportCommand(['omega', 'vendor:import', '--tag=test'], [
            'force' => false,
        ]);

        $random = now()->format('YmdHis') . microtime();

        AbstractServiceProvider::export(
            path: [
                $this->basePath . slash(path: '/fixtures/application-write/copy/from/file.txt') =>
                    $this->basePath . slash(path: '/fixtures/application-write/copy/to/file.txt')
            ],
            tag: 'test'
        );

        AbstractServiceProvider::export(
            path: [
                $this->basePath . slash(path: '/fixtures/application-write/copy/from/folder') =>
                    $this->basePath
                    . slash(path: '/fixtures/application-write/copy/to/folders/folder-')
                    . $random
            ],
            tag: 'test'
        );

        ob_start();
        $exit = $publish->main();
        ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(
            file_exists($this->basePath . slash(path: '/fixtures/application-write/copy/to/file.txt'))
        );
        $this->assertTrue(
            file_exists(
                $this->basePath
                . slash(path: '/fixtures/application-write/copy/to/folders/folder-')
                . $random
                . '/file.txt'
            )
        );
    }

    /**
     * Test it can import with tag.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanImportWithTag(): void
    {
        $publish = new VendorImportCommand(['omega', 'vendor:import', '--tag=test'], [
            'force' => false,
            'tag'   => 'test',
        ]);

        $random = now()->format('YmdHis') . microtime();

        AbstractServiceProvider::export(
            path: [
                $this->basePath . slash(path: '/fixtures/application-write/copy/from/file.txt') =>
                    $this->basePath . slash(path: '/fixtures/application-write/copy/to/file.txt')
            ],
            tag: 'test'
        );
        AbstractServiceProvider::export(
            path: [
                $this->basePath . slash(path: '/fixtures/application-write/copy/from/folder') =>
                    $this->basePath . slash(path: '/fixtures/application-write/copy/to/folders/folder-') . $random
            ],
            tag: 'vendor'
        );

        ob_start();
        $exit = $publish->main();
        ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertTrue(file_exists($this->basePath . slash(path: '/fixtures/application-write/copy/to/file.txt')));
        $this->assertFalse(
            file_exists(
                $this->basePath
                . slash(path: '/fixtures/application-write/copy/to/folders/folder-'
                    . $random
                    . '/file.txt')
            )
        );
    }
}
