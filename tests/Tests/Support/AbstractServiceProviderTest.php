<?php

/**
 * Part of Omega - Tests\Support Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support;

use Exception;
use Omega\Support\AbstractServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

use function chmod;
use function copy;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function unlink;

/**
 * Tests for the AbstractServiceProvider file import utilities.
 *
 * This test suite verifies the behavior of the file and directory import
 * helpers provided by AbstractServiceProvider. The tests simulate vendor
 * resource publishing using a controlled fixture environment on the
 * filesystem.
 *
 * Covered scenarios include:
 * - Creating destination directories when importing files
 * - Overwriting existing files when explicitly allowed
 * - Handling copy failures
 * - Recursive directory imports
 * - Handling missing or inaccessible source directories
 * - Throwing exceptions when overwrite is not permitted
 *
 * The filesystem operations are performed inside fixture directories to
 * ensure isolation and prevent side effects outside the test environment.
 *
 * @category  Tests
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(AbstractServiceProvider::class)]
final class AbstractServiceProviderTest extends TestCase
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
     * Test import creates destination directory.
     *
     * @return void
     * @throws Exception If the destination file exists and overwriting is not allowed
     */
    public function testImportCreatesDestinationDirectory(): void
    {
        $source = $this->basePath . '/fixtures/application-write/copy/from/file.txt';
        $target = $this->basePath . '/fixtures/application-write/tmp/newdir/file.txt';

        $dir    = dirname($target);

        if (file_exists($target)) {
            unlink($target);
        }

        if (is_dir($dir)) {
            rmdir($dir);
        }

        $result = AbstractServiceProvider::importFile($source, $target, true);

        $this->assertTrue($result);
        $this->assertTrue(file_exists($target));
        $this->assertTrue(is_dir($dir));

        unlink($target);
        rmdir($dir);
    }

    /**
     * Test import overwrites existing file.
     *
     * @return void
     * @throws Exception If the destination file exists and overwriting is not allowed
     */
    public function testImportOverwritesExistingsFile(): void
    {
        $source = $this->basePath . '/fixtures/application-write/copy/from/file.txt';
        $target = $this->basePath . '/fixtures/application-write/tmp/existing/file.txt';

        $dir    = dirname($target);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        copy($source, $target);

        $result = AbstractServiceProvider::importFile($source, $target, true);

        $this->assertTrue($result);
        $this->assertTrue(file_exists($target));

        unlink($target);
        rmdir($dir);
    }

    /**
     * Test import file return false if copy fails.
     *
     * @return void
     * @throws Exception If the destination file exists and overwriting is not allowed
     */
    public function testImportFileReturnsFalseIfCopyFails(): void
    {
        $source = $this->basePath . '/non_existent_file.txt';
        $target = $this->basePath . '/fixtures/application-write/tmp/target.txt';

        $result = @AbstractServiceProvider::importFile($source, $target, true);

        $this->assertFalse($result);
    }

    /**
     * Test importDir returns false if source directory cannot be opened.
     *
     * @return void
     * @throws Exception If the destination file exists and overwriting is not allowed
     */
    public function testImportDirReturnsFalseIfSourceDoesNotExist(): void
    {
        $nonExistentDir = $this->basePath . '/directory_che_non_esiste_mai';
        $targetDir      = $this->basePath . '/fixtures/application-write/tmp/target';

        $result         = @AbstractServiceProvider::importDir($nonExistentDir, $targetDir);

        $this->assertFalse($result, 'importDir should return false if the source directory does not exist.');
    }

    /**
     * Test import dir handles recursion.
     *
     * @return void
     * @throws Exception If the destination file exists and overwriting is not allowed
     */
    public function testImportDirHandlesRecursion(): void
    {
        $sourceDir  = $this->basePath . '/fixtures/application-write/recursive_test';
        $subDir     = $sourceDir . '/subdir';
        $sourceFile = $subDir . '/test.txt';

        if (!is_dir($subDir)) {
            mkdir($subDir, 0755, true);
        }

        file_put_contents($sourceFile, 'omega content');

        $targetDir = $this->basePath . '/fixtures/application-write/recursive_target';

        $result = AbstractServiceProvider::importDir($sourceDir, $targetDir, true);

        $this->assertTrue($result);
        $this->assertTrue(file_exists($targetDir . '/subdir/test.txt'));

        @unlink($sourceFile);
        @rmdir($subDir);
        @rmdir($sourceDir);
    }

    /**
     * Test import dir returns false if scandir fails on readable dir.
     *
     * @return void
     * @throws Exception If the destination file exists and overwriting is not allowed
     */
    public function testImportDirReturnsFalseIfScandirFailsOnReadableDir(): void
    {
        $protectedDir = $this->basePath . '/fixtures/application-write/inaccessible_dir';
        mkdir($protectedDir, 0755, true);

        chmod($protectedDir, 0333);

        $result = @AbstractServiceProvider::importDir($protectedDir, $this->basePath . '/target');

        $this->assertFalse($result);

        chmod($protectedDir, 0755);
        rmdir($protectedDir);
    }

    /**
     * Test import file where folder already exists.
     *
     * @return void
     * @throws Exception If the destination file exists and overwriting is not allowed
     */
    public function testImportFileWhereFolderAlreadyExists(): void
    {
        $targetDir = $this->basePath . '/fixtures/application-write/tmp/exists';

        mkdir($targetDir, 0755, true); // Prepariamo la cartella

        $source = $this->basePath . '/fixtures/application-write/copy/from/file.txt';
        $target = $targetDir . '/newfile.txt';

        $result = AbstractServiceProvider::importFile($source, $target, true);

        $this->assertTrue($result);

        unlink($target);
        rmdir($targetDir);
    }

    /**
     * Test import fle throws exception when file exits and no overwrite.
     *
     * @return void
     * @throws Exception If the destination file exists and overwriting is not allowed
     */
    public function testImportFileThrowsExceptionWhenFileExistsAndNoOverwrite(): void
    {
        $source = $this->basePath . '/fixtures/application-write/copy/from/file.txt';
        $target = $this->basePath . '/fixtures/application-write/copy/to/existing-file.txt';

        if (!file_exists(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        copy($source, $target);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have permission to overwrite the destination file.');

        AbstractServiceProvider::importFile($source, $target, false);

        @unlink($target);
    }
}
