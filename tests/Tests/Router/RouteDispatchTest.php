<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Route;
use Omega\Router\RouteDispatcher;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function call_user_func;
use function call_user_func_array;

#[CoversClass(Route::class)]
#[CoversClass(RouteDispatcher::class)]
#[CoversClass(Router::class)]
final class RouteDispatchTest extends TestCase
{
    protected function tearDown(): void
    {
        Router::reset();

        parent::tearDown();
    }

    public function testCanResultCurrentRoute(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/', 'GET', $this->routeDispatchRoutes());

        /** @noinspection PhpUnusedLocalVariableInspection */
        $dispatch = $dispatcher->run(
            fn (callable $callable, array $params) => call_user_func_array($callable, $params),
            fn (string $path) => 'not found - ',
            fn (string $path, string $method) => 'method not allowed - - ',
        );

        $current = $dispatcher->current();
        $this->assertInstanceOf(Route::class, $current);

        $realRoute = $this->routeDispatchRoutes()[0];
        $realRoute['expression'] = '^/$';

        $this->assertEquals($realRoute['method'], $current['method']);
        $this->assertEquals($realRoute['expression'], $current['expression']);
        $this->assertEquals($realRoute['name'], $current['name']);
        $realFunction    = $realRoute['function'];
        $currentFunction = $current['function'];

        $this->assertIsCallable($realFunction);
        $this->assertIsCallable($currentFunction);

        $this->assertEquals(call_user_func($realFunction), call_user_func($currentFunction));
    }

    public function testCanDispatchAndCall(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/', 'GET', $this->routeDispatchRoutes());

        $dispatch = $dispatcher->run(
            fn (callable $callable, array $params) => call_user_func_array($callable, $params),
            fn (string $path) => 'not found - ',
            fn (string $path, string $method) => 'method not allowed - - ',
        );

        $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

        $this->assertTrue($result);
    }

    public function testCanDispatchAndRunFound(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/', 'GET', $this->routeDispatchRoutes());

        $dispatch = $dispatcher->run(
            fn ()               => 'found',
            fn (string $path) => 'not found - ',
            fn (string $path, string $method) => 'method not allowed - - ',
        );

        $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

        $this->assertEquals('found', $result);
    }

    public function testCanDispatchAndRunNotFound(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/not-found', 'GET', $this->routeDispatchRoutes());

        $dispatch = $dispatcher->run(
            fn ()               => 'found',
            fn (string $path) => 'not found - ',
            fn (string $path, string $method) => 'method not allowed - - ',
        );

        $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

        $this->assertEquals('not found - ', $result);
    }

    public function testCanDispatchAndRunMethodNotAllowed(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/', 'POST', $this->routeDispatchRoutes());

        $dispatch = $dispatcher->run(
            fn ()               => 'found',
            fn (string $path) => 'not found - ',
            fn (string $path, string $method) => 'method not allowed - - ',
        );

        $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

        $this->assertEquals('method not allowed - - ', $result);
    }

    /**
     * @return array<int, Route>
     */
    private function routeDispatchRoutes(): array
    {
        return [
            new Route([
                'method'     => 'GET',
                'expression' => '/',
                'function'   => fn () => true,
            ]),
        ];
    }
}
