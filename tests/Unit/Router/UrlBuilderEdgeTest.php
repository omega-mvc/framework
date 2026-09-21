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

use Omega\Router\Exceptions\RouteUrlNotFullyResolvedException;
use Omega\Router\Route;
use Omega\Router\Router;
use Omega\Router\RouteUrlBuilder;

covers(Route::class);
covers(RouteUrlBuilder::class);

it('manages custom patterns through addPatterns and getPatterns', function (): void {
    $builder = new RouteUrlBuilder(['(:a)' => '[a-z]+']);

    $builder->addPatterns(['(:b)' => '\d+']);

    expect($builder->getPatterns())->toBe([
        '(:a)' => '[a-z]+',
        '(:b)' => '\d+',
    ]);
});

it('builds an empty url when the route exposes no uri', function (): void {
    $builder = new RouteUrlBuilder(Router::$patterns);

    expect($builder->buildUrl(new Route(['name' => 'nameless']), []))->toBe('');
});

it('skips pattern map entries whose key or value is not a string', function (): void {
    $builder = new RouteUrlBuilder(Router::$patterns);

    $route = new Route([
        'uri' => '/x',
    ]);
    $route['patterns'] = [0 => 1];

    expect($builder->buildUrl($route, []))->toBe('/x');
});

it('resolves pattern placeholders from numeric keys in associative parameter sets', function (): void {
    $builder = new RouteUrlBuilder(Router::$patterns);

    expect($builder->buildUrl(
        new Route(['uri' => '/user/(:id)']),
        ['other' => 'zz', 0 => 55]
    ))->toBe('/user/55');
});

it('resolves a named placeholder from a numeric parameter list', function (): void {
    $builder = new RouteUrlBuilder(Router::$patterns);

    expect($builder->buildUrl(new Route(['uri' => '/user/(id:num)']), [42]))->toBe('/user/42');
});

it('throws when a named placeholder remains unresolved in the url', function (): void {
    $builder = new RouteUrlBuilder(Router::$patterns);
    $message = 'Unresolved named placeholders remain in the generated URL.';

    expect(fn () => $builder->buildUrl(new Route(['uri' => '/user/(pid:all)']), ['pid' => '(x:y)']))
        ->toThrow(RouteUrlNotFullyResolvedException::class, $message);
});

it('throws when a registered pattern remains in the generated url', function (): void {
    $builder = new RouteUrlBuilder([
        'abc' => '.*',
        '(:x)' => '.*',
    ]);

    expect(fn () => $builder->buildUrl(new Route(['uri' => '/u/(:x)']), ['x' => 'abc']))
        ->toThrow(RouteUrlNotFullyResolvedException::class, 'Unresolved pattern "abc" remains in the generated URL.');
});