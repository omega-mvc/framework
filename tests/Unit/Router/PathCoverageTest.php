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
use Omega\Router\RouteUrlBuilder;
use Tests\Router\Support\TestMiddleware;

covers(AbstractRouter::class);
covers(Route::class);
covers(Router::class);
covers(RouteUrlBuilder::class);

beforeEach(function (): void {
    Router::reset();
});

afterEach(function (): void {
    Router::reset();
});

it('walks getRoutes for empty and growing route collections', function (): void {
    expect(Router::getRoutes())->toBe([]);

    Router::get('/one', fn () => 1);
    expect(Router::getRoutes())->toHaveCount(1);

    Router::get('/two', fn () => 2);
    expect(Router::getRoutes())->toHaveCount(2);

    Router::get('/three', fn () => 3);
    expect(Router::getRoutes())->toHaveCount(3);
});

it('groups routes with a name but without a middleware key', function (): void {
    Router::group(['as' => 'named.'], function (): void {
        Router::get('/named', fn () => 'n')->name('item');
    });

    expect(Router::getRoutes()[0]['name'])->toBe('named.item');
});

it('groups routes with an empty middleware list and no name key', function (): void {
    Router::group(['middleware' => []], function (): void {
        Router::get('/empty-mw', fn () => 'e');
    });

    expect(Router::getRoutes()[0]['middleware'] ?? [])->toBe([]);
});

it('groups routes with several middleware entries and no setup keys', function (): void {
    Router::group([], function (): void {
        Router::get('/plain-group', fn () => 'p');
    });

    Router::group([
        'middleware' => [TestMiddleware::class, TestMiddleware::class],
    ], function (): void {
        Router::get('/multi-mw', fn () => 'm');
    });

    $routes = Router::getRoutes();

    expect($routes[0]['uri'] ?? null)->toBe('/plain-group');
    expect($routes[1]['middleware'] ?? [])->toBe([TestMiddleware::class, TestMiddleware::class]);
});

it('redirect scans an empty route table, a middle route and a last route', function (): void {
    expect(fn () => Router::redirect('missing'))->toThrow(RouteNotFoundException::class);

    Router::get('/first', fn () => 1)->name('first');
    Router::get('/second', fn () => 2)->name('second');
    Router::get('/third', fn () => 3)->name('third');

    expect(Router::redirect('second')['uri'])->toBe('/second');
    expect(Router::redirect('third')['uri'])->toBe('/third');
});

it('merges middleware into an existing list and accepts empty additions', function (): void {
    $route = new Route(['uri' => '/mw', 'middleware' => [TestMiddleware::class]]);

    $route->middleware([]);
    expect($route['middleware'])->toBe([TestMiddleware::class]);

    $route->middleware([TestMiddleware::class, Route::class]);
    expect($route['middleware'])->toBe([TestMiddleware::class, TestMiddleware::class, Route::class]);
});

it('builds urls with non array, mixed and multi pattern maps', function (): void {
    $builder = new RouteUrlBuilder(['(:id)' => '(\d+)']);

    $nonArrayRoute = new Route(['uri' => '/u/(:id)']);
    $nonArrayRoute['patterns'] = 'nope';

    expect($builder->buildUrl($nonArrayRoute, [1]))->toBe('/u/1');
    expect($builder->buildUrl(new Route(['uri' => '/u/x']), []))->toBe('/u/x');

    $mixedRoute = new Route(['uri' => '/u/(:id)/(:id)']);
    $mixedRoute['patterns'] = [0 => 1, '(:id)' => '(\d+)'];

    expect($builder->buildUrl($mixedRoute, [1, 2]))->toBe('/u/1/2');
    expect($builder->buildUrl(
        new Route(['uri' => '/u/(:id)']),
        ['other' => 'zz', 0 => 5]
    ))->toBe('/u/5');
});

it('replaces every occurrence of a pattern and skips absent ones', function (): void {
    $builder = new RouteUrlBuilder([
        '(:a)' => '(\d+)',
        '(:b)' => '([a-z]+)',
        '(:c)' => '([A-Z]+)',
    ]);

    expect($builder->buildUrl(new Route(['uri' => '/(a:a)/(a:a)/(b:b)']), [1, 2, 'x']))
        ->toBe('/1/2/x');
    expect($builder->buildUrl(new Route(['uri' => '/static']), []))->toBe('/static');
});

it('validates a clean url against a multi pattern map', function (): void {
    $builder = new RouteUrlBuilder([
        '(:a)' => '(\d+)',
        '(:b)' => '([a-z]+)',
        '(:c)' => '([A-Z]+)',
    ]);

    expect($builder->buildUrl(new Route(['uri' => '/plain']), []))->toBe('/plain');
});
