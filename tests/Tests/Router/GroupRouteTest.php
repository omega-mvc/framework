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
use Omega\Router\Router;
use Tests\Router\Support\DispatcherTrait;
use Tests\Router\Support\SomeClass;

/**
 * Class GroupRouteTest
 *
 * This class contains PHPUnit tests for the routing system's support for route groups.
 *
 * It tests the following features:
 * - Creating custom route groups with a common prefix.
 * - Using a controller for a group of routes.
 * - Handling nested route prefixes for hierarchical routing structures.
 *
 * Each test uses a dispatcher helper to simulate HTTP requests and capture route outputs,
 * verifying that grouped routes behave correctly in terms of routing, prefixes, and controllers.
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
class GroupRouteTest extends TestCase
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
     * Test it can make custom route group.
     *
     * @return void
     */
    public function testItCanMakeCustomRouteGroup(): void
    {
        Router::group([
            'prefix' => '/test',
        ], function () {
            Router::get('/foo', [SomeClass::class, 'foo']);
        });
        Router::get('/bar', [SomeClass::class, 'bar']);

        $res = $this->dispatcher('/test/foo', 'get');
        $this->assertEquals('bar', $res);

        $res = $this->dispatcher('/bar', 'get');
        $this->assertEquals('foo', $res);
    }

    /**
     * Test it can use group controller.
     *
     * @return void
     */
    public function testItCanUseGroupController(): void
    {
        Router::controller(SomeClass::class)->group(function () {
            Router::get('/foo', 'foo');
            Router::get('/bar', 'bar');
        });

        $res = $this->dispatcher('/foo', 'get');
        $this->assertEquals('bar', $res);

        $res = $this->dispatcher('/bar', 'get');
        $this->assertEquals('foo', $res);
    }

    /**
     * Test it can handle nested prefixes.
     *
     * @return void
     */
    public function testItCanHandleNestedPrefixes(): void
    {
        Router::prefix('/api')->group(function () {
            Router::get('/status', function () {
                echo 'api-status';
            });

            Router::prefix('/v1')->group(function () {
                Router::get('/users', function () {
                    echo 'api-v1-users';
                });

                Router::prefix('/admin')->group(function () {
                    Router::get('/dashboard', function () {
                        echo 'api-v1-admin-dashboard';
                    });
                });
            });
        });

        $res = $this->dispatcher('/api/status', 'get');
        $this->assertEquals('api-status', $res);

        $res = $this->dispatcher('/api/v1/users', 'get');
        $this->assertEquals('api-v1-users', $res);

        $res = $this->dispatcher('/api/v1/admin/dashboard', 'get');
        $this->assertEquals('api-v1-admin-dashboard', $res);
    }
}
