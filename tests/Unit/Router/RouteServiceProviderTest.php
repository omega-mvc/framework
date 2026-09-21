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

use Omega\Application\Application;
use Omega\Middleware\MaintenanceMiddleware;
use Omega\Router\RouteServiceProvider;
use Omega\Router\Router;
use ReflectionProperty;

covers(RouteServiceProvider::class);

afterEach(function (): void {
    Router::reset();
    unset($GLOBALS['router_schedule_count']);
});

/**
 * Reset the once-per-process schedule flag so every test controls its own boot cycle.
 *
 * @return void
 */
function resetScheduleFlag(): void
{
    (new ReflectionProperty(RouteServiceProvider::class, 'scheduleLoaded'))->setValue(null, false);
}

/**
 * Build a fresh application rooted at the given Router fixture directory.
 *
 * @param string $baseType The fixtures' base directory name.
 * @return Application
 */
function routerProviderApp(string $baseType): Application
{
    return new Application(__DIR__ . '/fixtures/' . $baseType);
}

it('boot registers the web routes and loads the schedule exactly once', function (): void {
    resetScheduleFlag();

    $app      = routerProviderApp('support-no-cache');
    $provider = new RouteServiceProvider($app);

    $provider->boot();

    expect(Router::getRoutesRaw())->toHaveCount(1);
    expect(Router::getRoutesRaw()[0]['uri'])->toBe('/web');

    $provider->boot();

    expect($GLOBALS['router_schedule_count'])->toBe(1);

    $app->flush();
});

it('boot skips the schedule when its file is missing', function (): void {
    resetScheduleFlag();

    $app      = routerProviderApp('empty');
    $provider = new RouteServiceProvider($app);

    $provider->boot();

    expect($GLOBALS['router_schedule_count'] ?? null)->toBeNull();
    expect(Router::getRoutesRaw())->toBe([]);

    $app->flush();
});

it('registerWebRoutes loads cached route definitions', function (): void {
    $app      = routerProviderApp('support');
    $provider = new RouteServiceProvider($app);

    $provider->registerWebRoutes();

    $routes = Router::getRoutesRaw();

    expect($routes)->toHaveCount(4);

    $methods     = array_map(static fn ($route): mixed => $route['method'], $routes);
    $expressions = array_map(static fn ($route): mixed => $route['expression'], $routes);

    expect($methods)->toContain('get');
    expect($methods)->toContain('head');
    expect($expressions)->toContain('/closure');
    expect($expressions)->toContain('/strlen');
    expect($expressions)->toContain('/mixed');

    $app->flush();
});

it('registerWebRoutes ignores a cache file that does not return an array', function (): void {
    $app      = routerProviderApp('support-null-cache');
    $provider = new RouteServiceProvider($app);

    $provider->registerWebRoutes();

    expect(Router::getRoutesRaw())->toBe([]);

    $app->flush();
});

it('registerWebRoutes falls back to the web routes file when no cache exists', function (): void {
    $app      = routerProviderApp('support-no-cache');
    $provider = new RouteServiceProvider($app);

    $provider->registerWebRoutes();

    $routes = Router::getRoutesRaw();

    expect($routes)->toHaveCount(1);
    expect($routes[0]['uri'])->toBe('/web');
    expect($routes[0]['middleware'])->toBe([MaintenanceMiddleware::class]);

    $app->flush();
});

it('registerWebRoutes returns early when both the cache and the web routes file are missing', function (): void {
    $app      = routerProviderApp('empty');
    $provider = new RouteServiceProvider($app);

    $provider->registerWebRoutes();

    expect(Router::getRoutesRaw())->toBe([]);

    $app->flush();
});

it('registerWebRoutes returns early when the base path does not resolve to a string', function (): void {
    $app = routerProviderApp('support-no-cache');
    $app->set('path.base', ['/unexpected/path']);

    $provider = new RouteServiceProvider($app);

    $provider->registerWebRoutes();

    expect(Router::getRoutesRaw())->toBe([]);

    $app->flush();
});

it('boot skips the schedule when the base path does not resolve to a string', function (): void {
    resetScheduleFlag();

    $app = routerProviderApp('support-no-cache');
    $app->set('path.base', ['/unexpected/path']);

    $provider = new RouteServiceProvider($app);

    $provider->boot();

    expect(Router::getRoutesRaw())->toBe([]);
    expect($GLOBALS['router_schedule_count'] ?? null)->toBeNull();

    $app->flush();
});

it('drops cached routes whose method is neither a string nor an array', function (): void {
    $app      = routerProviderApp('support');
    $provider = new RouteServiceProvider($app);

    $provider->registerWebRoutes();

    $expressions = array_map(static fn ($route): mixed => $route['expression'], Router::getRoutesRaw());

    expect($expressions)->not->toContain('/numeric-method');
    expect($expressions)->toHaveCount(4);

    $app->flush();
});

it('re-registers the same cached definitions across several requests', function (): void {
    $app      = routerProviderApp('support');
    $provider = new RouteServiceProvider($app);

    foreach (range(1, 6) as $round) {
        $provider->registerWebRoutes();
    }

    expect(Router::getRoutesRaw())->toHaveCount(24);

    $app->flush();
});