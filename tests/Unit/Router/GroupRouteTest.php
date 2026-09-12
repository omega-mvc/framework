<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Http\Request;
use Omega\Router\RouteDispatcher;
use Omega\Router\Router;
use Tests\Router\Support\SomeClass;

use function Tests\Router\Support\dispatcher;

require_once __DIR__ . '/Support/Dispatcher.php';

covers(Request::class);
covers(RouteDispatcher::class);
covers(Router::class);

afterEach(function (): void {
    Router::reset();
});

it('can make custom route group', function (): void {
    Router::group([
        'prefix' => '/test',
    ], function () {
        Router::get('/foo', [SomeClass::class, 'foo']);
    });
    Router::get('/bar', [SomeClass::class, 'bar']);

    $res = dispatcher('/test/foo', 'get');
    expect($res)->toEqual('bar');

    $res = dispatcher('/bar', 'get');
    expect($res)->toEqual('foo');
});

it('can handle nested prefixes', function (): void {
    Router::prefix('/api')->group(function () {
        Router::get('/status', function () {
            echo 'api-status';
        });

        Router::prefix('/v1')->group(function () {
            Router::get('/users', function () {
                echo 'api-v1-users';
            });

            Router::prefix('/admin')->group(function () {
                Router::get('/dashboard', function () {
                    echo 'api-v1-admin-dashboard';
                });
            });
        });
    });

    $res = dispatcher('/api/status', 'get');
    expect($res)->toEqual('api-status');

    $res = dispatcher('/api/v1/users', 'get');
    expect($res)->toEqual('api-v1-users');

    $res = dispatcher('/api/v1/admin/dashboard', 'get');
    expect($res)->toEqual('api-v1-admin-dashboard');
});