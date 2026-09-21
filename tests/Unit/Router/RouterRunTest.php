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
use Omega\Router\RouteDispatcher;
use Omega\Router\RouteGroup;
use Omega\Router\Router;
use Tests\Router\Support\NoHandleMiddleware;
use Tests\Router\Support\TestMiddleware;

covers(AbstractRouter::class);
covers(RouteDispatcher::class);
covers(RouteGroup::class);
covers(Router::class);

afterEach(function (): void {
    Router::reset();
});

it('run falls back to server defaults when uri and method are omitted', function (): void {
    Router::get('/', fn () => 'root');

    unset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

    expect(Router::run())->toBe('root');
});

it('run accepts explicit uri and method arguments', function (): void {
    Router::post('/explicit', fn () => 'posted');

    expect(Router::run(uri: '/explicit', method: 'POST'))->toBe('posted');
});

it('run falls back to the server method when only the uri is passed', function (): void {
    Router::get('/server-method', fn () => 'served');

    $_SERVER['REQUEST_METHOD'] = 'GET';

    expect(Router::run(uri: '/server-method'))->toBe('served');
});

it('run returns null when no route matches and no not-found handler is set', function (): void {
    Router::get('/only', fn () => 'ok');

    expect(Router::run(uri: '/missing', method: 'GET'))->toBeNull();
});

it('run returns null when the method is not allowed and no handler is set', function (): void {
    Router::get('/only', fn () => 'ok');

    expect(Router::run(uri: '/only', method: 'POST'))->toBeNull();
});

it('run matches a route against a non-empty base path', function (): void {
    Router::get('/users', fn () => 'users');

    expect(Router::run(basePath: '/api', uri: '/api/users', method: 'GET'))->toBe('users');
});

it('run honours case sensitive matching', function (): void {
    Router::get('/CaseRoute', fn () => 'case-matched');

    expect(Router::run(caseMatters: true, uri: '/CaseRoute', method: 'GET'))->toBe('case-matched');
    expect(Router::run(caseMatters: true, uri: '/caseroute', method: 'GET'))->toBeNull();
    expect(Router::run(caseMatters: false, uri: '/caseroute', method: 'GET'))->toBe('case-matched');
});

it('run picks the first match by default and the last match in multiMatch mode', function (): void {
    Router::get('/multi', fn () => 'one');
    Router::get('/multi', fn () => 'two');

    expect(Router::run(multiMatch: false, uri: '/multi', method: 'GET'))->toBe('one');
    expect(Router::run(multiMatch: true, uri: '/multi', method: 'GET'))->toBe('two');
});

it('run instantiates middleware that does not expose a handle method', function (): void {
    Router::get('/mw', fn () => 'mw')->middleware([NoHandleMiddleware::class]);

    expect(Router::run(uri: '/mw', method: 'GET'))->toBe('mw');
});

it('run executes duplicated middleware only once per request', function (): void {
    TestMiddleware::$last = 0;

    Router::middleware([TestMiddleware::class])->group(function (): void {
        Router::get('/dup', fn () => 'dup')->middleware([TestMiddleware::class]);
    });

    Router::run(uri: '/dup', method: 'GET');

    expect(TestMiddleware::$last)->toBe(1);

    TestMiddleware::$last = 0;
});