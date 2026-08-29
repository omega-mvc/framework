<?php

/**
 * Part of Omega - Tests\Application Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Tests\Application;

use Exception;
use InvalidArgumentException;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ApplicationNotAvailableException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

use function Omega\Application\app;
use function Omega\Application\get_path;
use function Omega\Application\is_dev;
use function Omega\Application\is_production;
use function Omega\Application\os_detect;
use function Omega\Application\path;
use function Omega\Application\set_path;
use function Omega\Application\slash;

/**
 * Test suite for Omega global helper functions.
 *
 * This class verifies the behavior and consistency of all core helper
 * functions provided by the Omega\Application namespace.
 *
 * The tests cover:
 * - Application container access via the `app()` helper, including lifecycle
 *   handling and exception scenarios after flushing the application instance.
 * - Filesystem and path utilities including `path()`, `set_path()`,
 *   `get_path()`, and `slash()`, with special attention to:
 *     - Support for both string and array inputs
 *     - Correct normalization of directory separators
 *     - Proper handling of suffixes and dot-notation conversion
 *     - Validation and exception handling for invalid inputs
 * - Operating system detection via `os_detect()`, including all supported
 *   OS families and default fallback behavior.
 * - Integration of helpers with the application container, ensuring
 *   bindings are correctly resolved and returned values are accurate.
 *
 * This suite ensures that helper functions behave consistently across
 * different input types (scalar vs array), maintain cross-platform
 * compatibility, and correctly integrate with the underlying application
 * infrastructure.
 *
 * @category  Tests
 * @package   Application
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversFunction('Omega\Application\app')]
#[CoversFunction('Omega\Application\get_path')]
#[CoversFunction('Omega\Application\is_dev')]
#[CoversFunction('Omega\Application\is_production')]
#[CoversFunction('Omega\Application\os_detect')]
#[CoversFunction('Omega\Application\path')]
#[CoversFunction('Omega\Application\set_path')]
#[CoversFunction('Omega\Application\slash')]
final class HelperTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test it throw error after flush application.
     *
     * @return void
     */
    public function testItThrowErrorAfterFlushApplication(): void
    {
        $app = new Application('/');
        $app->flush();

        $this->expectException(ApplicationNotAvailableException::class);
        app();
        app()->flush();
    }

    /**
     * Test it can load app.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanLoadApp(): void
    {
        $app = new Application('');

        $this->assertEquals('/', app()->get('path.base'));

        $app->flush();
    }

    /**
     * Test environment helpers.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testEnvironmentHelpers(): void
    {
        $app = new Application($this->setFixtureBasePath());

        $app->set('environment', 'prod');
        $this->assertFalse(is_dev());
        $this->assertTrue(is_production());
    }

    /**
     * Test os detect identifies all supported families.
     *
     * @return void
     */
    public function testOsDetectIdentifiesAllSupportedFamilies(): void
    {
        $this->assertEquals('windows', os_detect('Windows'));
        $this->assertEquals('linux',   os_detect('Linux'));
        $this->assertEquals('mac',     os_detect('Darwin'));
        $this->assertEquals('bsd',     os_detect('Bsd'));
        $this->assertEquals('solaris', os_detect('Solaris'));
        $this->assertEquals('unknown', os_detect('AmigaOS')); // Ramo default

        $currentOs = strtolower(PHP_OS_FAMILY);
        $expected = match($currentOs) {
            'darwin' => 'mac',
            'windows', 'linux', 'bsd', 'solaris' => $currentOs,
            default => 'unknown'
        };

        $this->assertEquals($expected, os_detect());
    }

    /**
     * Test slash handles both strings and array.
     *
     * @return void
     */
    public function testSlashHandlesBothStringsAndArrays(): void
    {
        $separator = DIRECTORY_SEPARATOR;

        $this->assertEquals("a{$separator}b", slash('a/b'));
        $this->assertEquals("c", slash('c')); // Caso senza slash per il path coverage

        $input = ['a/b', 'c/d'];
        $expected = ["a{$separator}b", "c{$separator}d"];

        $this->assertEquals($expected, slash($input));
    }

    /**
     * Test path normalization.
     *
     * @return void
     */
    public function testPathNormalization(): void
    {
        $ds = DIRECTORY_SEPARATOR;

        $this->assertEquals("app{$ds}config{$ds}", path('app.config'));

        $this->assertEquals("app{$ds}logs{$ds}", path("app.logs{$ds}"));

        $input = ['core.view', 'cache'];
        $expected = ["core{$ds}view{$ds}", "cache{$ds}"];
        $this->assertEquals($expected, path($input));

        $this->assertEquals("{$ds}", path(''));
    }

    /**
     * Test get path with array and suffix.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testGetPathWithArrayAndSuffix(): void
    {
        $app = new Application(__DIR__);
        $ds  = DIRECTORY_SEPARATOR;

        $paths = [
            'logs' => 'storage/logs/',
            'cache' => 'storage/framework/cache/'
        ];
        $app->set('custom_paths', $paths);

        $result = get_path('custom_paths', 'daily/');

        $expected = [
            'logs' => "storage/logs/daily{$ds}",
            'cache' => "storage/framework/cache/daily{$ds}"
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Test get path with string and suffix.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testGetPathWithStringAndSuffix(): void
    {
        $app = new Application(__DIR__);
        $ds  = DIRECTORY_SEPARATOR;

        $app->set('single_path', 'app/core/');

        $result = get_path('single_path', 'test/');

        $this->assertSame("app/core/test{$ds}", $result);
    }

    /**
     * Test set path with single string.
     *
     * @return void
     */
    public function testSetPathWithSingleString(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $input = 'app.config.storage';
        $expected = "{$ds}app{$ds}config{$ds}storage{$ds}";

        $this->assertSame($expected, set_path($input));
    }

    /**
     * Test set path with array of strings.
     *
     * @return void
     */
    public function testSetPathWithArrayOfStrings(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $input = ['app.config', 'public.assets'];
        $expected = [
            "{$ds}app{$ds}config{$ds}",
            "{$ds}public{$ds}assets{$ds}"
        ];

        $this->assertSame($expected, set_path($input));
    }

    /**
     * Test set path throws eception on empty string.
     *
     * @return void
     */
    public function testSetPathThrowsExceptionOnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path key cannot be an empty string');

        set_path('');
    }

    /**
     * Test set path throws exception on empty array.
     *
     * @return void
     */
    public function testSetPathThrowsExceptionOnEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);

        set_path([]);
    }
}
