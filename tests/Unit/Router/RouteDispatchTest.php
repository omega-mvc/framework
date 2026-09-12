<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Route;
use Omega\Router\RouteDispatcher;
use Omega\Router\Router;

use function call_user_func_array;

covers(Route::class);
covers(RouteDispatcher::class);
covers(Router::class);

/**
 * @return array<int, Route>
 */
function routeDispatchRoutes(): array
{
    return [
        new Route([
            'method'     => 'GET',
            'expression' => '/',
            'function'   => fn() => true,
        ]),
    ];
}

afterEach(function (): void {
    Router::reset();
});

it('can result current route', function (): void {
    $dispatcher = RouteDispatcher::dispatchFrom('/', 'GET', routeDispatchRoutes());

    /** @noinspection PhpUnusedLocalVariableInspection */
    $dispatch = $dispatcher->run(
        fn (callable $callable, array $params) => call_user_func_array($callable, $params),
        fn ($path)              => 'not found - ',
        fn ($path, $method)     => 'method not allowed - - ',
    );

    $current = $dispatcher->current();
    $this->assertInstanceOf(Route::class, $current);

    $realRoute = routeDispatchRoutes()[0];
    $realRoute['expression'] = '^/$';

    expect($current['method'])->toEqual($realRoute['method']);
    expect($current['expression'])->toEqual($realRoute['expression']);
    expect($current['name'])->toEqual($realRoute['name']);
$realFunction    = $realRoute['function'];
    $currentFunction = $current['function'];

    $this->assertIsCallable($realFunction);
    $this->assertIsCallable($currentFunction);

    expect(call_user_func($realFunction))->toEqual(call_user_func($currentFunction));
});

it('can dispatch and call', function (): void {
    $dispatcher = RouteDispatcher::dispatchFrom('/', 'GET', routeDispatchRoutes());

    $dispatch = $dispatcher->run(
        fn (callable $callable, array $params) => call_user_func_array($callable, $params),
        fn ($path)              => 'not found - ',
        fn ($path, $method)     => 'method not allowed - - ',
    );

    $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

    expect($result)->toBeTrue();
});

it('can dispatch and run found', function (): void {
    $dispatcher = RouteDispatcher::dispatchFrom('/', 'GET', routeDispatchRoutes());

    $dispatch = $dispatcher->run(
        fn ()               => 'found',
        fn ($path)          => 'not found - ',
        fn ($path, $method) => 'method not allowed - - ',
    );

    $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

    expect($result)->toEqual('found');
});

it('can dispatch and run not found', function (): void {
    $dispatcher = RouteDispatcher::dispatchFrom('/not-found', 'GET', routeDispatchRoutes());

    $dispatch = $dispatcher->run(
        fn ()               => 'found',
        fn ($path)          => 'not found - ',
        fn ($path, $method) => 'method not allowed - - ',
    );

    $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

    expect($result)->toEqual('not found - ');
});

it('can dispatch and run method not allowed', function (): void {
    $dispatcher = RouteDispatcher::dispatchFrom('/', 'POST', routeDispatchRoutes());

    $dispatch = $dispatcher->run(
        fn ()               => 'found',
        fn ($path)          => 'not found - ',
        fn ($path, $method) => 'method not allowed - - ',
    );

    $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

    expect($result)->toEqual('method not allowed - - ');
});