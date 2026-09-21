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

use Omega\Router\Route;
use Omega\Router\RouteDispatcher;

use function call_user_func_array;

covers(Route::class);
covers(RouteDispatcher::class);

/**
 * Build a trivial route definition.
 *
 * @param string $expression Route URI expression.
 * @param string $method     Allowed HTTP method(s).
 * @return Route
 */
function edgeRoute(string $expression, string $method = 'GET'): Route
{
    return new Route([
        'method'     => $method,
        'expression' => $expression,
        'function'   => fn (): string => 'handler-' . $expression,
    ]);
}

it('treats a url without a path component as root', function (): void {
    $dispatcher = RouteDispatcher::dispatchFrom('http://localhost', 'GET', [edgeRoute('/')]);

    $dispatch = $dispatcher->run(
        fn (callable $callable, array $params) => 'root',
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect(call_user_func_array($dispatch['callable'], $dispatch['params']))->toBe('root');
});

it('trims trailing slashes by default', function (): void {
    $dispatcher = RouteDispatcher::dispatchFrom('/foo/', 'GET', [edgeRoute('/foo')]);

    $dispatch = $dispatcher->run(
        fn (callable $callable, array $params) => call_user_func_array($callable, $params),
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect(call_user_func_array($dispatch['callable'], $dispatch['params']))->toBe('handler-/foo');
});

it('keeps trailing slashes when trailingSlashMatters is enabled', function (): void {
    $dispatcher = RouteDispatcher::dispatchFrom('/foo/', 'GET', [edgeRoute('/foo')])
        ->trailingSlashMatters(true);

    $dispatch = $dispatcher->run(
        fn (callable $callable, array $params) => 'found',
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect(call_user_func_array($dispatch['callable'], $dispatch['params']))->toBe('not-found');
});

it('matches case insensitively by default and strictly when caseMatters is enabled', function (): void {
    $routes = [edgeRoute('/Case/Sensitive')];

    $default = RouteDispatcher::dispatchFrom('/case/sensitive', 'GET', $routes);
    $strict  = RouteDispatcher::dispatchFrom('/case/sensitive', 'GET', $routes)->caseMatters(true);

    $defaultDispatch = $default->run(
        fn (callable $callable, array $params) => 'found',
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect(call_user_func_array($defaultDispatch['callable'], $defaultDispatch['params']))->toBe('found');

    $strictDispatch = $strict->run(
        fn (callable $callable, array $params) => 'found',
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect(call_user_func_array($strictDispatch['callable'], $strictDispatch['params']))->toBe('not-found');
});

it('combines basePath with the route expression when matching', function (): void {
    $dispatcher = RouteDispatcher::dispatchFrom('/api/users', 'GET', [edgeRoute('/users')])
        ->basePath('/api');

    $dispatch = $dispatcher->run(
        fn (callable $callable, array $params) => call_user_func_array($callable, $params),
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect(call_user_func_array($dispatch['callable'], $dispatch['params']))->toBe('handler-/users');
});

it('returns the last matching route in multiMatch mode', function (): void {
    $routes = [
        edgeRoute('/multi'),
        edgeRoute('/multi'),
    ];

    $single = RouteDispatcher::dispatchFrom('/multi', 'GET', $routes)->multiMatch(false);
    $multi  = RouteDispatcher::dispatchFrom('/multi', 'GET', $routes)->multiMatch(true);

    $singleDispatch = $single->run(
        fn (callable $callable, array $params) => call_user_func_array($callable, $params),
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect(call_user_func_array($singleDispatch['callable'], $singleDispatch['params']))->toBe('handler-/multi');

    $multiDispatch = $multi->run(
        fn (callable $callable, array $params) => call_user_func_array($callable, $params),
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    $multiResult = call_user_func_array($multiDispatch['callable'], $multiDispatch['params']);

    expect($multiResult)->toBe('handler-/multi');
    expect($multi->current())->toBe($routes[1]);
});

it('resolves named parameters during dispatch', function (): void {
    $captured = null;

    $dispatcher = RouteDispatcher::dispatchFrom('/user/42', 'GET', [
        new Route([
            'method'     => 'GET',
            'expression' => '/user/(?P<id>\d+)',
            'function'   => fn (string $id): string => 'user-' . $id,
        ]),
    ]);

    $dispatch = $dispatcher->run(
        function (callable $callable, array $params) use (&$captured): string {
            $captured = $params;

            return call_user_func_array($callable, $params);
        },
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

    expect($captured)->toBe(['id' => '42']);
    expect($result)->toBe('user-42');
});

it('applies per route patterns during dispatch', function (): void {
    $route = new Route([
        'method'     => 'GET',
        'expression' => '/post/{id}',
        'patterns'   => ['{id}' => '\d+'],
        'function'   => fn (): string => 'post',
    ]);

    $matching = RouteDispatcher::dispatchFrom('/post/123', 'GET', [$route]);
    $failing  = RouteDispatcher::dispatchFrom('/post/abc', 'GET', [$route]);

    $foundDispatch = $matching->run(
        fn (callable $callable, array $params) => 'found',
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect(call_user_func_array($foundDispatch['callable'], $foundDispatch['params']))->toBe('found');

    $missingDispatch = $failing->run(
        fn (callable $callable, array $params) => 'found',
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect(call_user_func_array($missingDispatch['callable'], $missingDispatch['params']))->toBe('not-found');
});

it('skips non matching methods and dispatches on the first available', function (): void {
    $dispatcher = RouteDispatcher::dispatchFrom('/multi-method', 'POST', [
        new Route([
            'method'     => ['get', 'post'],
            'expression' => '/multi-method',
            'function'   => fn (): string => 'handled',
        ]),
    ]);

    $dispatch = $dispatcher->run(
        fn (callable $callable, array $params) => 'found',
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect(call_user_func_array($dispatch['callable'], $dispatch['params']))->toBe('found');
});

it('sets the current route expression from the original expression', function (): void {
    $route     = new Route([
        'method'     => 'GET',
        'expression' => '/about',
        'function'   => fn (): string => 'about',
    ]);
    $dispatcher = RouteDispatcher::dispatchFrom('/about', 'GET', [$route]);

    $dispatcher->run(
        fn (callable $callable, array $params) => 'found',
        fn ($path) => 'not-found',
        fn ($path, $method) => 'not-allowed',
    );

    expect($dispatcher->current())->toBeInstanceOf(Route::class);
    expect($dispatcher->current()['expression'])->toBe('^/about$');
});