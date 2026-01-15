<?php

/**
 * Part of Omega - Tests\Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Router\Route;
use Omega\Router\Router;
use Omega\Router\RouteUrlBuilder;

/**
 * Class UrlBuilderTest
 *
 * A PHPUnit test case for testing the RouteUrlBuilder component of the Omega Router system.
 *
 * This class validates the URL generation functionality, ensuring that:
 *   - Standard and named route parameters are correctly replaced in the URL.
 *   - Mixed usage of indexed and named parameters works as expected.
 *   - Base paths can be prepended to generated URLs.
 *   - All supported pattern types (num, text, slug, any, etc.) are handled properly.
 *   - Custom patterns defined per route are respected.
 *   - Edge cases, such as zero and empty string values, are correctly processed.
 *   - Complex nested route structures are supported.
 *
 * @category  Tests
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Route::class)]
#[CoversClass(Router::class)]
#[CoversClass(RouteUrlBuilder::class)]
final class UrlBuilderTest extends TestCase
{
    /**
     * An instance of RouteUrlBuilder used to generate URLs from Route objects
     * with various parameter types and patterns. It is initialized before each
     * test and cleared after each test to ensure isolation.
     *
     * @var RouteUrlBuilder|null
     */
    private ?RouteUrlBuilder $builder;

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
        $this->builder = new RouteUrlBuilder(Router::$patterns);
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
        $this->builder = null;
    }

    /**
     * Test it can generate simple standard pattern.
     *
     * @return void
     */
    public function testItCanGenerateSimpleStandardPattern(): void
    {
        $this->assertSame(
            '/user/123',
            $this->builder->buildUrl(
                new Route([
                    'uri' => '/user/(:id)',
                ]),
                [123]
            )
        );
    }

    /**
     * Test it cna generate multiple standard patterns.
     *
     * @return void
     */
    public function testItCanGenerateMultipleStandardPatterns(): void
    {
        $this->assertSame(
            '/user/123/profile/john-doe',
            $this->builder->buildUrl(
                new Route([
                    'uri' => '/user/(:id)/profile/(:slug)',
                ]),
                [123, 'john-doe']
            )
        );
    }

    /**
     * Test it can generate with named parameters only.
     *
     * @return void
     */
    public function testItCanGenerateWithNamedParametersOnly(): void
    {
        $this->assertSame(
            '/absensi/456/today',
            $this->builder->buildUrl(
                new Route([
                    'uri' => '/absensi/(identitas:id)/(tanggal:text)',
                ]),
                [
                    'identitas' => 456,
                    'tanggal'   => 'today',
                ]
            )
        );
    }

    /**
     * Test it can mix indexed and named parameters.
     *
     * @return void
     */
    public function testItCanMixIndexedAndNamedParameters(): void
    {
        $this->assertSame(
            '/user/123/absensi/456/hari-ini',
            $this->builder->buildUrl(
                new Route([
                    'uri' => '/user/(:id)/absensi/(identitas:id)/hari-ini',
                ]),
                [
                    0           => 123,
                    'identitas' => 456,
                ]
            )
        );
    }

    /**
     * Test it can generate with base path.
     *
     * @return void
     */
    public function testItCanGenerateWithBasePath(): void
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->assertSame(
            '/admin/users/999/edit',
            $this->builder->buildUrl(
                new Route([
                    'uri' => '/admin/(section:text)/(userId:id)/edit',
                ]),
                [
                    'section' => 'users',
                    'userId'  => 999,
                ],
                '/backend'
            )
        );
    }

    /**
     * Test it can generate with all pattern types.
     *
     * @return void
     */
    public function testItCanGenerateWithAllPatternTypes(): void
    {
        $this->assertSame(
            '/api/1/query_123/page/5/active-users',
            $this->builder->buildUrl(
                new Route([
                    'uri' => '/api/(:id)/(search:any)/page/(:num)/(filter:slug)',
                ]),
                [
                    'id'     => 1,
                    'search' => 'query_123',
                    'num'    => 5,
                    'filter' => 'active-users',
                ]
            )
        );
    }

    /**
     * Test it can generate with custom pattern.
     *
     * @return void
     */
    public function testItCanGenerateWithCustomPattern(): void
    {
        $this->assertSame(
            '/color/ff00ff',
            $this->builder->buildUrl(
                new Route([
                    'uri'      => '/color/(:hex)',
                    'patterns' => ['(:hex)' => '([0-9a-fA-F]+)'],
                ]),
                ['ff00ff']
            )
        );
    }

    /**
     * Test it can handle zero and empty string values.
     *
     * @return void
     */
    public function testItCanHandleZeroAndEmptyStringValues(): void
    {
        $this->assertSame(
            '/user/0/profile/',
            $this->builder->buildUrl(
                new Route([
                    'uri' => '/user/(:id)/profile/(:text)',
                ]),
                [0, '']
            )
        );
    }

    /**
     * Test it generate complex nested style.
     *
     * @return void
     */
    public function testItCanGenerateComplexNestedStyle(): void
    {
        $this->assertSame(
            '/company/1/employee/456/profile/john-doe/large',
            $this->builder->buildUrl(
                new Route([
                    'uri' => '/company/(:id)/employee/(empId:num)/profile/(:slug)/(avatar:text)',
                ]),
                [
                    0        => 1,
                    'empId'  => 456,
                    1        => 'john-doe',
                    'avatar' => 'large',
                ]
            )
        );
    }

    /**
     * Test it can generate multiple same pattern types.
     *
     * @return void
     */
    public function testItCanGenerateMultipleSamePatternTypes(): void
    {
        $this->assertSame(
            '/tags/php/related/laravel',
            $this->builder->buildUrl(
                new Route([
                    'uri' => '/tags/(:slug)/related/(:slug)',
                ]),
                ['php', 'laravel']
            )
        );
    }
}
