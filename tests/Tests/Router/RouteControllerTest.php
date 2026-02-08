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

use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Router\Support\DispatcherTrait;
use Tests\Router\Support\EmptyRouteClassController;
use Tests\Router\Support\RouteClassController;

/**
 * Class RouteControllerTest
 *
 * A PHPUnit test case for testing the Omega Router's resource controller functionality.
 *
 * This class verifies the correct registration and dispatching of resource-based routes,
 * including:
 *   - Full resource routing (index, create, store, show, edit, update, destroy)
 *   - Partial resources using "only" and "except" options
 *   - Resource route name generation, including namespacing and prefix handling
 *   - Modifying resource route maps
 *   - Custom handling of missing routes
 *
 * The `dispatcher` helper method simulates an HTTP request and returns the route output
 * as a string, capturing normal, not found, and method not allowed cases.
 *
 * @category  Tests
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Router::class)]
final class RouteControllerTest extends TestCase
{
    use DispatcherTrait;

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
        Router::reset();
    }

    /**
     * Test it can route using resource controller.
     *
     * @return void
     */
    public function testItCanRouteUsingResourceController(): void
    {
        Router::resource('/', RouteClassController::class);

        $res = $this->dispatcher('/', 'get');
        $this->assertEquals('works', $res);

        $res = $this->dispatcher('/create', 'get');
        $this->assertEquals('works create', $res);

        $res = $this->dispatcher('/', 'post');
        $this->assertEquals('works store', $res);

        $res = $this->dispatcher('/12', 'get');
        $this->assertEquals('works show', $res);

        $res = $this->dispatcher('/12/edit', 'get');
        $this->assertEquals('works edit', $res);

        $res = $this->dispatcher('/12', 'put');
        $this->assertEquals('works update', $res);

        $res = $this->dispatcher('/12', 'delete');
        $this->assertEquals('works destroy', $res);
    }

    /**
     * Test it can route using resource controller with custom only.
     *
     * @return void
     */
    public function testItCanRouteUsingResourceControllerWithCustomOnly(): void
    {
        Router::resource('/', RouteClassController::class, [
            'only' => ['index'],
        ]);

        $res = $this->dispatcher('/', 'get');
        $this->assertEquals('works', $res);

        $res = $this->dispatcher('/create', 'get');
        $this->assertEquals('not found', $res);

        $res = $this->dispatcher('/', 'post');
        $this->assertEquals('not allowed', $res);

        $res = $this->dispatcher('/12', 'get');
        $this->assertEquals('not found', $res);

        $res = $this->dispatcher('/12/edit', 'get');
        $this->assertEquals('not found', $res);

        $res = $this->dispatcher('/12', 'put');
        $this->assertEquals('not found', $res);

        $res = $this->dispatcher('/12', 'delete');
        $this->assertEquals('not found', $res);
    }

    /**
     * Test it can route using resource controller with custom only using chain.
     *
     * @return void
     */
    public function testItCanRouteUsingResourceControllerWithCustomOnlyUsingChain(): void
    {
        Router::resource('/', RouteClassController::class)
            ->only(['index']);

        $res = $this->dispatcher('/', 'get');
        $this->assertEquals('works', $res);

        $res = $this->dispatcher('/create', 'get');
        $this->assertEquals('not found', $res);

        $res = $this->dispatcher('/', 'post');
        $this->assertEquals('not allowed', $res);

        $res = $this->dispatcher('/12', 'get');
        $this->assertEquals('not found', $res);

        $res = $this->dispatcher('/12/edit', 'get');
        $this->assertEquals('not found', $res);

        $res = $this->dispatcher('/12', 'put');
        $this->assertEquals('not found', $res);

        $res = $this->dispatcher('/12', 'delete');
        $this->assertEquals('not found', $res);
    }

    /**
     * Test it can route using resource controller with custom except.
     *
     * @return void
     */
    public function testItCanRouteUsingResourceControllerWithCustomExcept(): void
    {
        Router::resource('/', RouteClassController::class, [
            'except' => ['store'],
        ]);

        $res = $this->dispatcher('/', 'get');
        $this->assertEquals('works', $res);

        $res = $this->dispatcher('/', 'post');
        $this->assertEquals('not allowed', $res);

        $res = $this->dispatcher('/create', 'get');
        $this->assertEquals('works create', $res);

        $res = $this->dispatcher('/12', 'get');
        $this->assertEquals('works show', $res);

        $res = $this->dispatcher('/12/edit', 'get');
        $this->assertEquals('works edit', $res);

        $res = $this->dispatcher('/12', 'put');
        $this->assertEquals('works update', $res);

        $res = $this->dispatcher('/12', 'delete');
        $this->assertEquals('works destroy', $res);
    }

    /**
     * Test it can route using resource controller with custom except using chain.
     *
     * @return void
     */
    public function testItCanRouteUsingResourceControllerWithCustomExceptUsingChain(): void
    {
        Router::resource('/', RouteClassController::class)
            ->except(['store']);

        $res = $this->dispatcher('/', 'get');
        $this->assertEquals('works', $res);

        $res = $this->dispatcher('/', 'post');
        $this->assertEquals('not allowed', $res);

        $res = $this->dispatcher('/create', 'get');
        $this->assertEquals('works create', $res);

        $res = $this->dispatcher('/12', 'get');
        $this->assertEquals('works show', $res);

        $res = $this->dispatcher('/12/edit', 'get');
        $this->assertEquals('works edit', $res);

        $res = $this->dispatcher('/12', 'put');
        $this->assertEquals('works update', $res);

        $res = $this->dispatcher('/12', 'delete');
        $this->assertEquals('works destroy', $res);
    }

    /**
     * Test it route resource have name.
     *
     * @return void
     */
    public function testItRouteResourceHaveName(): void
    {
        Router::resource('/', RouteClassController::class);

        $this->assertTrue(Router::has('Tests\Router\Support\RouteClassController.index'));
        $this->assertTrue(Router::has('Tests\Router\Support\RouteClassController.create'));
        $this->assertTrue(Router::has('Tests\Router\Support\RouteClassController.store'));
        $this->assertTrue(Router::has('Tests\Router\Support\RouteClassController.show'));
        $this->assertTrue(Router::has('Tests\Router\Support\RouteClassController.edit'));
        $this->assertTrue(Router::has('Tests\Router\Support\RouteClassController.destroy'));
    }

    /**
     * Test it route resource have name with prefix.
     *
     * @return void
     */
    public function testItRouteResourceHaveNameWithPrefix(): void
    {
        Router::name('test.')->group(function () {
            Router::resource('/', RouteClassController::class);
        });

        $this->assertTrue(Router::has('test.Tests\Router\Support\RouteClassController.index'));
        $this->assertTrue(Router::has('test.Tests\Router\Support\RouteClassController.create'));
        $this->assertTrue(Router::has('test.Tests\Router\Support\RouteClassController.store'));
        $this->assertTrue(Router::has('test.Tests\Router\Support\RouteClassController.show'));
        $this->assertTrue(Router::has('test.Tests\Router\Support\RouteClassController.edit'));
        $this->assertTrue(Router::has('test.Tests\Router\Support\RouteClassController.destroy'));
    }

    /**
     * Test it can modify resource map.
     *
     * @return void
     */
    public function testItCanModifyResourceMap(): void
    {
        Router::resource('/', EmptyRouteClassController::class, [
            'map' => ['index' => 'api'],
        ]);
        $res = $this->dispatcher('/', 'get');
        $this->assertEquals('works api', $res);
    }

    /**
     * Test it can modify resource map using chain.
     *
     * @return void
     */
    public function testItCanModifyResourceMapUsingChain(): void
    {
        Router::resource('/', EmptyRouteClassController::class)
            ->map(['index' => 'api', 'create' => 'apiCreate']);
        $res = $this->dispatcher('/', 'get');
        $this->assertEquals('works api', $res);
        $res = $this->dispatcher('/create', 'get');
        $this->assertEquals('works apiCreate', $res);
    }

    /**
     * Test it can be custom resource when missing.
     *
     * @return void
     */
    public function testItCanCustomResourceWhenMissing(): void
    {
        Router::resource('/', EmptyRouteClassController::class)
            ->missing(function () {
                echo '404';
            });
        $res = $this->dispatcher('/', 'get');
        $this->assertEquals('404', $res);
    }

    /**
     * Test it can be custom resource when  missing using setup.
     *
     * @return void
     */
    public function testItCanCustomResourceWhenMissingUsingSetup(): void
    {
        Router::resource('/', EmptyRouteClassController::class, [
            'missing' => function () {
                echo '404';
            },
        ]);
        $res = $this->dispatcher('/', 'get');
        $this->assertEquals('404', $res);
    }
}
