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
use Omega\Router\Router;
use ReflectionMethod;
use ReflectionProperty;

use function call_user_func_array;

covers(Route::class);
covers(RouteDispatcher::class);
covers(Router::class);

beforeEach(function (): void {
    Router::reset();
});

afterEach(function (): void {
    Router::reset();
});

/**
 * Dispatch the given routes and return the triggered outcome label.
 *
 * @param Route[]                                                    $routes  Route instances to dispatch against.
 * @param string                                                     $uri     Request URI.
 * @param string                                                     $method  Request HTTP method.
 * @param array{basePath?: string, caseMatters?: bool, trailingSlashMatters?: bool, multiMatch?: bool} $options Dispatcher options.
 * @return string One of: found, notFound, methodNotAllowed.
 */
function dispatchOutcome(array $routes, string $uri, string $method, array $options = []): string
{
    $dispatcher = RouteDispatcher::dispatchFrom($uri, $method, $routes)
        ->basePath($options['basePath'] ?? '')
        ->caseMatters($options['caseMatters'] ?? false)
        ->trailingSlashMatters($options['trailingSlashMatters'] ?? false)
        ->multiMatch($options['multiMatch'] ?? false);

    $dispatch = $dispatcher->run(
        fn (callable $callable, array $params): string => 'found',
        fn (string $path): string => 'notFound',
        fn (string $path, string $method): string => 'methodNotAllowed',
    );

    $outcome = call_user_func_array($dispatch['callable'], $dispatch['params']);

    if (!is_string($outcome)) {
        throw new \LogicException('Dispatcher callbacks must return a string outcome label.');
    }

    return $outcome;
}

/**
 * Build a route instance from a partial definition.
 *
 * @param array{method?: string|array<int, string>, uri?: string, expression?: string, function?: mixed,
 *   patterns?: array<string, string>, middleware?: array<int, class-string>, name?: string} $data Route definition.
 * @return Route
 */
function dispatchRoute(array $data): Route
{
    return new Route($data + [
        'method'   => 'get',
        'function' => static fn (): string => 'route',
    ]);
}

it('triggers notFound when the route table is empty', function (): void {
    $_SERVER['REQUEST_URI'] = '/anything';

    expect(dispatchOutcome([], '/anything', 'GET'))->toBe('notFound');
});

it('matches a plain route with a string method', function (): void {
    $route = dispatchRoute(['expression' => '/plain']);

    expect(dispatchOutcome([$route], '/plain', 'GET'))->toBe('found');
    expect(dispatchOutcome([$route], '/plain', 'POST'))->toBe('methodNotAllowed');
});

it('matches a route whose method list needs several iterations', function (): void {
    $route = dispatchRoute(['expression' => '/list', 'method' => ['post', 'put', 'get']]);

    expect(dispatchOutcome([$route], '/list', 'PUT'))->toBe('found');
});

it('does not enter the method loop for an empty method list', function (): void {
    $route = dispatchRoute(['expression' => '/empty-methods', 'method' => []]);

    expect(dispatchOutcome([$route], '/empty-methods', 'GET'))->toBe('methodNotAllowed');
});

it('continues over non matching routes before finding a match', function (): void {
    $routes = [
        dispatchRoute(['expression' => '/first']),
        dispatchRoute(['expression' => '/second']),
    ];

    expect(dispatchOutcome($routes, '/second', 'GET'))->toBe('found');
});

it('stops at the first match unless multiMatch is enabled', function (): void {
    $first  = dispatchRoute(['expression' => '/multi']);
    $second = dispatchRoute(['expression' => '/multi']);

    expect(dispatchOutcome([$first, $second], '/multi', 'GET'))->toBe('found');

    $dispatcher = RouteDispatcher::dispatchFrom('/multi', 'GET', [$first, $second])->multiMatch(true);
    $dispatcher->run(
        fn (callable $callable, array $params): string => 'found',
        fn (string $path): string => 'notFound',
        fn (string $path, string $method): string => 'methodNotAllowed',
    );

    expect($dispatcher->current())->toBe($second);
});

it('applies a base path prefix during matching', function (): void {
    $route = dispatchRoute(['expression' => '/users']);

    expect(dispatchOutcome([$route], '/api/users', 'GET', ['basePath' => '/api']))->toBe('found');
    expect(dispatchOutcome([$route], 'api/users', 'GET', ['basePath' => 'api']))->toBe('notFound');
    expect(dispatchOutcome([$route], '/users', 'GET', ['basePath' => '/']))->toBe('found');
    expect(dispatchOutcome([$route], '/users', 'GET'))->toBe('found');
});

it('honours case sensitivity while matching', function (): void {
    $route = dispatchRoute(['expression' => '/CaseSensitive']);

    expect(dispatchOutcome([$route], '/casesensitive', 'GET', ['caseMatters' => true]))
        ->toBe('notFound');
    expect(dispatchOutcome([$route], '/casesensitive', 'GET'))
        ->toBe('found');
});

it('honours trailing slash significance while matching', function (): void {
    $route = dispatchRoute(['expression' => '/trailing']);

    expect(dispatchOutcome([$route], '/trailing/', 'GET', ['trailingSlashMatters' => true]))
        ->toBe('notFound');
    expect(dispatchOutcome([$route], '/trailing/', 'GET'))
        ->toBe('found');
});

it('keeps the bare base path unchanged when the request path equals it with a trailing slash', function (): void {
    $route = dispatchRoute(['expression' => '/users']);

    expect(dispatchOutcome([$route], '/api/', 'GET', ['basePath' => '/api']))
        ->toBe('notFound');
});

it('strips the query string before matching and tolerates missing paths', function (): void {
    $route = dispatchRoute(['expression' => '/query']);

    expect(dispatchOutcome([$route], '/query?a=1&b=2', 'GET'))->toBe('found');
    expect(dispatchOutcome([$route], '', 'GET'))->toBe('notFound');
    expect(dispatchOutcome([$route], 'http://', 'GET'))->toBe('notFound');
});

it('resolves named parameters and per route patterns', function (): void {
    $named = new Route([
        'expression' => '/user/((\d+))',
        'method'     => 'get',
        'function'   => static fn (string $id): string => 'user-' . $id,
    ]);

    expect(dispatchOutcome([$named], '/user/42', 'GET'))->toBe('found');

    $patterned = dispatchRoute(['expression' => '/item/(:num)', 'patterns' => ['(:num)' => '(\d+)']]);
    expect(dispatchOutcome([$patterned], '/item/7', 'GET'))->toBe('found');
});

it('triggers notFound and methodNotAllowed with null callbacks via reflection', function (): void {
    $missingRoute = dispatchRoute(['expression' => '/exists', 'method' => 'post']);

    $dispatcher = RouteDispatcher::dispatchFrom('/exists', 'GET', [$missingRoute]);

    (new ReflectionProperty(RouteDispatcher::class, 'notFound'))->setValue($dispatcher, null);
    (new ReflectionProperty(RouteDispatcher::class, 'methodNotAllowed'))->setValue($dispatcher, null);

    $method = new ReflectionMethod(RouteDispatcher::class, 'dispatch');
    $method->invoke($dispatcher, '', false, false, false);

    expect($dispatcher->current())->toBeNull();

    $noRoute = RouteDispatcher::dispatchFrom('/missing', 'GET', []);

    (new ReflectionProperty(RouteDispatcher::class, 'notFound'))->setValue($noRoute, null);
    (new ReflectionProperty(RouteDispatcher::class, 'methodNotAllowed'))->setValue($noRoute, null);

    (new ReflectionMethod(RouteDispatcher::class, 'dispatch'))->invoke($noRoute, '', false, false, false);

    expect($noRoute->current())->toBeNull();
});

it('covers every fallback callback combination via reflection', function (): void {
    $pathMatch    = dispatchRoute(['expression' => '/hit', 'method' => 'post']);
    $pathMismatch = dispatchRoute(['expression' => '/miss']);

    $scenarios = [
        'pathMatch, methodNotAllowed null'    => ['/hit', [$pathMatch], 'GET', null, fn (): string => 'nf'],
        'pathMatch, notFound null'            => ['/hit', [$pathMatch], 'GET', fn (): string => 'mna', null],
        'pathMatch, all null'                 => ['/hit', [$pathMatch], 'GET', null, null],
        'pathMismatch, both null'             => ['/nope', [$pathMismatch], 'GET', null, null],
        'pathMismatch, methodNotAllowed null' => ['/nope', [$pathMismatch], 'GET', null, fn (): string => 'nf'],
        'pathMismatch, all callable'          => ['/nope', [$pathMismatch], 'GET', fn (): string => 'mna', fn (): string => 'nf'],
    ];

    // Several identical rounds accumulate consecutive traces for every shape.
    foreach (range(1, 6) as $round) {
        foreach ($scenarios as $scenario => [$uri, $routes, $method, $mna, $nf]) {
            $dispatcher = RouteDispatcher::dispatchFrom($uri, $method, $routes);

            (new ReflectionProperty(RouteDispatcher::class, 'methodNotAllowed'))->setValue($dispatcher, $mna);
            (new ReflectionProperty(RouteDispatcher::class, 'notFound'))->setValue($dispatcher, $nf);

            (new ReflectionMethod(RouteDispatcher::class, 'dispatch'))->invoke($dispatcher, '', false, false, false);
        }
    }

    expect(count($scenarios))->toBe(6);
});

it('matches only the last of several consecutive non-matching routes', function (): void {
    $routes = array_map(
        static fn (int $i): Route => dispatchRoute(['expression' => "/miss-{$i}"]),
        range(1, 5)
    );

    $routes[] = dispatchRoute(['expression' => '/hit']);

    expect(dispatchOutcome($routes, '/hit', 'GET'))->toBe('found');
});

it('accumulates consecutive method-not-allowed and not-found dispatches', function (): void {
    $postOnly = dispatchRoute(['expression' => '/post-only', 'method' => 'post']);
    $plain    = dispatchRoute(['expression' => '/plain']);

    foreach (range(1, 6) as $round) {
        expect(dispatchOutcome([$postOnly], '/post-only', 'GET'))->toBe('methodNotAllowed');
        expect(dispatchOutcome([$plain], '/missing', 'GET'))->toBe('notFound');
    }
});

it('matches a route whose expression key is missing and exposes middleware', function (): void {
    $route = new Route([
        'method'     => 'get',
        'function'   => static fn (): string => 'x',
        'middleware' => [Support\TestMiddleware::class],
    ]);

    expect(dispatchOutcome([$route], '', 'GET'))->toBe('found');
});
