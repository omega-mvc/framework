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

use Omega\Router\Exceptions\RouteNotRegisteredException;
use Omega\Router\Route;
use Omega\Router\Router;
use Tests\Router\Support\TestMiddleware;

covers(Route::class);

afterEach(function (): void {
    Router::reset();
});

it('returns the whole definition through the route() magic method', function (): void {
    $route = new Route(['uri' => '/x', 'method' => 'get']);

    expect($route->route())->toBe(['uri' => '/x', 'method' => 'get', 'name' => '']);
});

it('throws RouteNotRegisteredException for unknown dynamic methods', function (): void {
    $route = new Route(['uri' => '/x']);

    expect(fn () => $route->__call('unknownMagic', []))
        ->toThrow(RouteNotRegisteredException::class, 'Route property or method [unknownMagic] is not registered.');
});

it('defaults the route name to an empty string', function (): void {
    $route = new Route(['uri' => '/x']);

    expect($route['name'])->toBe('');
});

it('prepends the router group as prefix to the route name', function (): void {
    $backup        = Router::$group;
    Router::$group = ['prefix' => '/web', 'middleware' => [], 'as' => 'web.'];

    $route = new Route(['uri' => '/x', 'name' => 'home']);

    expect($route['name'])->toBe('web.home');

    $route->name('other');

    expect($route['name'])->toBe('web.other');

    Router::$group = $backup;
});

it('appends middleware to the existing middleware list', function (): void {
    $route = new Route(['uri' => '/x', 'middleware' => [TestMiddleware::class]]);

    $result = $route->middleware([Route::class, TestMiddleware::class]);

    expect($result)->toBe($route);
    expect($route['middleware'])->toBe([
        TestMiddleware::class,
        Route::class,
        TestMiddleware::class,
    ]);
});

it('creates the middleware list from scratch when no middleware is present', function (): void {
    $route = new Route(['uri' => '/x']);

    $route->middleware([TestMiddleware::class]);

    expect($route['middleware'])->toBe([TestMiddleware::class]);
});

it('stores custom patterns through where()', function (): void {
    $route = new Route(['uri' => '/x/{id}']);

    $result = $route->where(['{id}' => '\d+']);

    expect($result)->toBe($route);
    expect($route['patterns'])->toBe(['{id}' => '\d+']);
});

it('supports the ArrayAccess contract', function (): void {
    $route = new Route(['uri' => '/x', 'name' => 'n']);

    expect(isset($route['name']))->toBeTrue();
    expect(isset($route['missing']))->toBeFalse();
    expect($route['missing'])->toBeNull();

    $route['extra'] = 'value';
    expect($route['extra'])->toBe('value');
    expect(isset($route['extra']))->toBeTrue();

    unset($route['extra']);
    expect(isset($route['extra']))->toBeFalse();
    expect($route['extra'])->toBeNull();
});