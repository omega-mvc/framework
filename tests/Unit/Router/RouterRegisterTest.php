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
use ReflectionException;
use Tests\Router\Attribute\TestBasicRouteAttribute;
use Tests\Router\Attribute\TestRouteAttribute;
use Tests\Router\Support\AttributeCombinationController;
use Tests\Router\Support\AttributeMiddlewareNameController;
use Tests\Router\Support\AttributeMiddlewareNamePrefixOrderedController;
use Tests\Router\Support\AttributeMiddlewarePrefixController;
use Tests\Router\Support\AttributeNamePrefixController;
use Tests\Router\Support\AttributeSingleMiddlewareController;
use Tests\Router\Support\AttributeSingleNameController;
use Tests\Router\Support\AttributeSinglePrefixController;
use Tests\Router\Support\NoRouteAttributeController;
use Tests\Router\Support\TestMiddleware;

covers(Router::class);

afterEach(function (): void {
    Router::reset();
});

it('registers a single class using attributes at class and method level', function (): void {
    Router::register(TestRouteAttribute::class);

    $routes = Router::getRoutes();

    expect($routes)->toHaveCount(1);

    $route = $routes[0];

    expect($route['uri'] ?? null)->toBe('/test/{id}/test');
    expect($route['name'])->toBe('test.test');
    expect($route['method'])->toBe(['GET']);
    expect($route['patterns'] ?? [])->toBe(['{id}' => '(\d+)']);
    expect($route['expression'] ?? null)->toBe('/test/{id}/test');
    expect($route['function'])->toBe([TestRouteAttribute::class, 'index']);
    expect($route['middleware'] ?? [])->toBe([TestMiddleware::class, TestMiddleware::class]);
});

it('registers a class whose methods carry plain http route attributes', function (): void {
    Router::register(TestBasicRouteAttribute::class);

    $routes = Router::getRoutes();

    expect($routes)->toHaveCount(7);

    $methods = array_map(static fn (array $route): mixed => $route['method'], $routes);

    expect($methods)->toContain(['GET']);
    expect($methods)->toContain(['POST']);
    expect($methods)->toContain(['DELETE']);
    expect($methods)->toContain(['put', 'patch']);
});

it('registers multiple classes when given an array of class names', function (): void {
    Router::register([TestBasicRouteAttribute::class]);

    expect(Router::getRoutesRaw())->toHaveCount(7);

    Router::reset();

    Router::register([TestBasicRouteAttribute::class, NoRouteAttributeController::class]);

    expect(Router::getRoutesRaw())->toHaveCount(7);
});

it('ignores classes whose methods expose no route attribute', function (): void {
    Router::register(NoRouteAttributeController::class);

    expect(Router::getRoutesRaw())->toBe([]);
});

it('throws a reflection exception when registering a missing class', function (): void {
    expect(fn () => Router::register('Missing\\Router\\Controller'))->toThrow(ReflectionException::class);
});

it('registers classes whose class attributes combine in different orders', function (): void {
    Router::register([
        AttributeCombinationController::class,
        AttributeMiddlewarePrefixController::class,
        AttributeNamePrefixController::class,
        AttributeSingleMiddlewareController::class,
        AttributeSingleNameController::class,
        AttributeSinglePrefixController::class,
        AttributeMiddlewareNameController::class,
        AttributeMiddlewareNamePrefixOrderedController::class,
    ]);

    $routes = Router::getRoutes();

    expect($routes)->toHaveCount(8);

    expect($routes[0]['method'])->toBe(['GET']);
    expect($routes[1]['method'])->toBe(['GET']);
    expect($routes[2]['method'])->toBe(['get']);

    expect($routes[0]['uri'] ?? null)->toBe('/combo/all');
    expect($routes[0]['name'])->toBe('combo.all');
    expect($routes[0]['middleware'] ?? [])->toBe([TestMiddleware::class, TestMiddleware::class]);
    expect($routes[0]['patterns'] ?? [])->toBe(['{id}' => '(\d+)']);

    expect($routes[1]['uri'] ?? null)->toBe('/mp/ping');
    expect($routes[1]['name'])->toBe('');

    expect($routes[2]['uri'] ?? null)->toBe('/np/item');
    expect($routes[2]['name'])->toBe('np.');

    expect($routes[3]['uri'] ?? null)->toBe('/mw');
    expect($routes[3]['middleware'] ?? [])->toBe([TestMiddleware::class]);
    expect($routes[3]['name'])->toBe('');

    expect($routes[4]['uri'] ?? null)->toBe('/named');
    expect($routes[4]['middleware'] ?? [])->toBe([]);
    expect($routes[4]['name'])->toBe('n.');

    expect($routes[5]['uri'] ?? null)->toBe('/single/prefixed');
    expect($routes[5]['middleware'] ?? [])->toBe([]);
    expect($routes[5]['name'])->toBe('');

    expect($routes[6]['uri'] ?? null)->toBe('/pair');
    expect($routes[6]['middleware'] ?? [])->toBe([TestMiddleware::class]);
    expect($routes[6]['name'])->toBe('mn.');

    expect($routes[7]['uri'] ?? null)->toBe('/full/index');
    expect($routes[7]['middleware'] ?? [])->toBe([TestMiddleware::class]);
    expect($routes[7]['name'])->toBe('full.');

    Router::reset();

    Router::register(AttributeCombinationController::class);

    expect(Router::getRoutesRaw())->toHaveCount(1);
});