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
use Omega\Router\RouteDispatcher;
use Omega\Router\Router;

use function call_user_func_array;

/**
 * Class RouteDispatchTest
 *
 * A PHPUnit test case for testing the behavior of the RouteDispatcher in the Omega Router system.
 *
 * This test class verifies the dispatching mechanism for registered routes, ensuring that:
 *   - The current route can be correctly retrieved after dispatching.
 *   - Routes are properly dispatched and their associated callables are executed.
 *   - Not-found routes trigger the correct fallback behavior.
 *   - Requests with unsupported HTTP methods trigger the method-not-allowed handler.
 *
 * The tests simulate HTTP requests and capture the results of dispatching, validating
 * that both route matching and callback execution behave as expected.
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
#[CoversClass(RouteDispatcher::class)]
#[CoversClass(Router::class)]
class RouteDispatchTest extends TestCase
{
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
     * Returns an array of Route objects for testing purposes.
     *
     * Each Route in the array contains the following keys:
     * - 'method': string HTTP method for the route (e.g., 'GET').
     * - 'expression': string route pattern used for matching (e.g., '/').
     * - 'function': callable Closure to execute when the route matches.
     *
     * @return Route[] An array of Route objects, each representing a single test route.
     */
    private function routes(): array
    {
        return [
            new Route([
                'method'     => 'GET',
                'expression' => '/',
                'function'   => fn() => true,
            ]),
        ];
    }

    /**
     * Test it can result current route.
     *
     * @return void
     */
    public function testItCanResultCurrentRoute(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/', 'GET', $this->routes());

        /** @noinspection PhpUnusedLocalVariableInspection */
        $dispatch = $dispatcher->run(
            fn ($callable, $params) => call_user_func_array($callable, $params),
            fn ($path)              => 'not found - ',
            fn ($path, $method)     => 'method not allowed - - ',
        );

        $current = $dispatcher->current();
        $realRoute = $this->routes()[0];
        $realRoute['expression'] = '^/$';

        $this->assertEquals($realRoute['method'], $current['method']);
        $this->assertEquals($realRoute['expression'], $current['expression']);
        $this->assertEquals($realRoute['name'], $current['name']);

        $this->assertIsCallable($current['function']);
        $this->assertEquals(call_user_func($realRoute['function']), call_user_func($current['function']));
    }

    /**
     * Test it can dispatch and call.
     *
     * @return void
     */
    public function testItCanDispatchAndCall(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/', 'GET', $this->routes());

        $dispatch = $dispatcher->run(
            fn ($callable, $params) => call_user_func_array($callable, $params),
            fn ($path)              => 'not found - ',
            fn ($path, $method)     => 'method not allowed - - ',
        );

        $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

        $this->assertTrue($result);
    }

    /**
     * Test it can dispatch and tun found.
     *
     * @return void
     */
    public function testItCanDispatchAndRunFound(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/', 'GET', $this->routes());

        $dispatch = $dispatcher->run(
            fn ()               => 'found',
            fn ($path)          => 'not found - ',
            fn ($path, $method) => 'method not allowed - - ',
        );

        $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

        $this->assertEquals('found', $result);
    }

    /**
     * Test it can dispatch and run not found.
     *
     * @return void
     */
    public function testItCanDispatchAndRunNotFound(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/not-found', 'GET', $this->routes());

        $dispatch = $dispatcher->run(
            fn ()               => 'found',
            fn ($path)          => 'not found - ',
            fn ($path, $method) => 'method not allowed - - ',
        );

        $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

        $this->assertEquals('not found - ', $result);
    }

    /**
     * Test it can dispatch and run method now allowed.
     *
     * @return void
     */
    public function testItCanDispatchAndRunMethodNotAllowed(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/', 'POST', $this->routes());

        $dispatch = $dispatcher->run(
            fn ()               => 'found',
            fn ($path)          => 'not found - ',
            fn ($path, $method) => 'method not allowed - - ',
        );

        $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

        $this->assertEquals('method not allowed - - ', $result);
    }
}
