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

use Omega\Router\AbstractRouter;
use Omega\Router\Exceptions\RouteNotFoundException;
use Omega\Router\Route;
use Omega\Router\Router;
use Tests\Router\Support\TestMiddleware;

use function ob_get_clean;
use function ob_start;

covers(AbstractRouter::class);
covers(Router::class);

afterEach(function (): void {
    Router::reset();
});

it('returns route definitions from getRoutes and raw instances from getRoutesRaw', function (): void {
    Router::get('/static', fn () => 'x')->name('static');

    $routes   = Router::getRoutes();
    $rawRoutes = Router::getRoutesRaw();

    expect($routes)->toHaveCount(1);
    expect($routes[0]['name'])->toBe('static');
    expect($routes[0]['uri'])->toBe('/static');

    expect($rawRoutes)->toHaveCount(1);
    expect($rawRoutes[0])->toBeInstanceOf(Route::class);
});

it('removes routes by name', function (): void {
    Router::get('/a', fn () => 'a')->name('keep');
    Router::get('/b', fn () => 'b')->name('remove');

    Router::removeRoutes('remove');

    expect(Router::has('remove'))->toBeFalse();
    expect(Router::has('keep'))->toBeTrue();
    expect(Router::getRoutesRaw())->toHaveCount(1);
});

it('replaces an existing route by name', function (): void {
    Router::get('/old', fn () => 'old')->name('target');

    $replacement = new Route([
        'name'       => 'target',
        'method'     => 'post',
        'uri'        => '/new',
        'expression' => '/new',
        'function'   => fn () => 'new',
    ]);

    Router::changeRoutes('target', $replacement);

    $routes = Router::getRoutes();

    expect($routes)->toHaveCount(1);
    expect($routes[0]['uri'])->toBe('/new');
    expect($routes[0]['method'])->toBe('post');
});

it('leaves the route table untouched when the name is not registered', function (): void {
    Router::get('/old', fn () => 'old')->name('target');

    $replacement = new Route([
        'name'       => 'other',
        'method'     => 'delete',
        'uri'        => '/elsewhere',
        'expression' => '/elsewhere',
        'function'   => fn () => 'elsewhere',
    ]);

    Router::changeRoutes('missing', $replacement);

    $routes = Router::getRoutes();

    expect($routes)->toHaveCount(1);
    expect($routes[0]['uri'])->toBe('/old');
});

it('merges multiple route definitions in one call', function (): void {
    Router::mergeRoutes([
        ['expression' => '/m1', 'function' => fn () => 'one', 'method' => 'get'],
        ['expression' => '/m2', 'function' => fn () => 'two', 'method' => 'post'],
    ]);

    expect(Router::getRoutesRaw())->toHaveCount(2);
    expect(Router::getRoutes()[1]['method'])->toBe('post');
});

it('registers an any() route for every http method', function (): void {
    Router::any('/any', fn () => 'any');

    expect(Router::getRoutes()[0]['method'])->toBe([
        'get',
        'head',
        'post',
        'put',
        'patch',
        'delete',
        'options',
    ]);
});

it('registers a patch() route', function (): void {
    Router::patch('/patch-only', fn () => 'patched');

    expect(Router::getRoutes()[0]['method'])->toBe('patch');
});

it('registers put(), delete() and options() routes', function (): void {
    Router::put('/put-only', fn () => 'put');
    Router::delete('/delete-only', fn () => 'delete');
    Router::options('/options-only', fn () => 'options');

    $methods = array_map(static fn (array $route): mixed => $route['method'], Router::getRoutes());

    expect($methods)->toBe(['put', 'delete', 'options']);
});

it('redirect() returns the matching route or throws when missing', function (): void {
    Router::get('/home', fn () => 'home')->name('home');

    expect(Router::redirect('home'))->toBeInstanceOf(Route::class);
    expect(fn () => Router::redirect('nowhere'))
        ->toThrow(RouteNotFoundException::class, 'Route [nowhere] not found.');
});

it('getCurrent is null before dispatch and holds the matched route afterwards', function (): void {
    Router::reset();

    expect(Router::getCurrent())->toBeNull();

    Router::get('/current', fn () => 'ok');
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI']    = '/current';
    Router::run();

    expect(Router::getCurrent())->toBeInstanceOf(Route::class);
    expect(Router::getCurrent()['uri'])->toBe('/current');

    Router::reset();

    expect(Router::getCurrent())->toBeNull();
});

it('group falls back to the current group prefix when no prefix key is given', function (): void {
    Router::prefix('/base')->group(function (): void {
        Router::get('/x', fn () => 'x');
    });

    Router::group([], function (): void {
        Router::get('/y', fn () => 'y');
    });

    $uris = array_map(static fn (array $route): string => $route['uri'], Router::getRoutes());

    expect($uris)->toContain('/base/x');
    expect($uris)->toContain('/y');
});

it('group applies the as name prefix and merges middleware', function (): void {
    Router::group([
        'as'         => 'admin.',
        'middleware' => [TestMiddleware::class],
    ], function (): void {
        Router::get('/dashboard', fn () => 'dash')->name('dashboard');
    });

    $routes = Router::getRoutes();

    expect($routes)->toHaveCount(1);
    expect($routes[0]['name'])->toBe('admin.dashboard');
    expect($routes[0]['middleware'])->toBe([TestMiddleware::class]);
});

it('group falls back to the current group as name prefix', function (): void {
    Router::group(['as' => 'api.'], function (): void {
        Router::group([], function (): void {
            Router::get('/ping', fn () => 'pong')->name('ping');
        });
    });

    expect(Router::getRoutes()[0]['name'])->toBe('api.ping');
});

it('group reset restores the previous prefix and middleware state', function (): void {
    Router::prefix('/outer')->group(function (): void {
        Router::group([
            'prefix'     => '/inner',
            'middleware' => [TestMiddleware::class],
        ], function (): void {
            Router::get('/inside', fn () => 'i');
        });
    });

    Router::get('/after', fn () => 'a');

    $routes  = Router::getRoutes();
    $outside = $routes[1];

    expect($outside['uri'])->toBe('/after');
    expect($outside['middleware'])->toBe([]);
});

it('mapPatterns expands aliases and named expressions', function (): void {
    expect(Router::mapPatterns('/u/(id:num)', Router::$patterns))->toBe('/u/(?P<id>([0-9]*))');
    expect(Router::mapPatterns('/(slug:unknown)', []))->toBe('/(?P<slug>[^/]+)');
    expect(Router::mapPatterns('/plain', Router::$patterns))->toBe('/plain');
});

it('dispatches a route whose alias is not part of the known patterns', function (): void {
    Router::get('/wild/(slug:whatever)', function (string $slug): void {
        echo 'wild-' . $slug;
    });

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI']    = '/wild/anything-goes';

    ob_start();
    Router::run();
    $output = ob_get_clean();

    expect($output)->toBe('wild-anything-goes');
});