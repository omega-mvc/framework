<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Router;
use Tests\Router\Support\TestMiddleware;

use function ob_get_clean;
use function ob_start;

covers(Router::class);

function basicRegisterRoutes(): void
{
    Router::get('/test', function () {
        echo 'render success';
    })->name('route.test');

    Router::get('/test/number/(:id)', function (string $id) {
        echo 'render success, with id is - ' . $id;
    })->name('route.test.number');

    Router::get('/test/text/(:text)', function (string $id) {
        echo 'render success, with id is - ' . $id;
    })->name('route.test.text');

    Router::get('/test/any/(:any)', function (string $id) {
        echo 'render success, with id is - ' . $id;
    })->name('route.test.any');

    Router::get('/test/any/(:all)', function (string $id) {
        echo 'render success, with id is - ' . $id;
    });
}

function basicRegisterGroupRoutes(): void
{
    Router::prefix('/page/')->group(function () {
        Router::get('one', function () {
            echo 'page one';
        });
        Router::get('two', function () {
            echo 'page two';
        });
    });
}

function basicRegisterRouterDifferentMethod(): void
{
    Router::match(['get'], '/get', function () {
        echo 'render success using get';
    })->name('name_is_get');
    Router::match(['head'], '/head', function () {
        echo 'render success using get over head method';
    });
    Router::match(['post'], '/post', function () {
        echo 'render success using post';
    });
    Router::match(['put'], '/put', function () {
        echo 'render success using put';
    });
    Router::match(['patch'], '/patch', function () {
        echo 'render success using patch';
    });
    Router::match(['delete'], '/delete', function () {
        echo 'render success using delete';
    });
    Router::match(['options'], '/options', function () {
        echo 'render success using options';
    });
}

function basicRegisterRouterMethodNotAllowed(): void
{
    Router::methodNotAllowed(function () {
        echo 'method not allowed';
    });
}

function basicRegisterRouterNotFound(): void
{
    Router::pathNotFound(function () {
        echo 'page not found 404';
    });
}

function basicGetResponse(string $method, string $url): false|string
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI']    = $url;

    ob_start();
    Router::run('/');

    return ob_get_clean();
}

it('route can be render', function (): void {
    basicRegisterRoutes();

    $routeBasic  = basicGetResponse('get', '/test');
    $routeNumber = basicGetResponse('get', '/test/number/123');
    $routeText   = basicGetResponse('get', '/test/text/xyz');
    $routeAny    = basicGetResponse('get', '/test/any/xyz+123');
    $routeAll    = basicGetResponse('get', '/test/any/xyz 123'); // allow all character

    expect($routeBasic)->toEqual('render success');
    expect($routeNumber)->toEqual('render success, with id is - 123');
    expect($routeText)->toEqual('render success, with id is - xyz');
    expect($routeAny)->toEqual('render success, with id is - xyz+123');
    expect($routeAll)->toEqual('render success, with id is - xyz 123');
});

it('route can be render using group prefix', function (): void {
    basicRegisterGroupRoutes();
    $getOne = basicGetResponse('get', '/page/one');
    $getTwo = basicGetResponse('get', '/page/two');

    expect($getOne)->toEqual('page one');
    expect($getTwo)->toEqual('page two');
});

it('route can be render different method', function (): void {
    basicRegisterRouterDifferentMethod();

    $get     = basicGetResponse('get', '/get');
    $head    = basicGetResponse('head', '/head');
    $post    = basicGetResponse('post', '/post');
    $put     = basicGetResponse('put', '/put');
    $patch   = basicGetResponse('patch', '/patch');
    $delete  = basicGetResponse('delete', '/delete');
    $options = basicGetResponse('options', '/options');

    expect($get)->toEqual('render success using get');
    expect($head)->toEqual('render success using get over head method');
    expect($post)->toEqual('render success using post');
    expect($put)->toEqual('render success using put');
    expect($patch)->toEqual('render success using patch');
    expect($delete)->toEqual('render success using delete');
    expect($options)->toEqual('render success using options');
});

it('route is method not allowed', function (): void {
    basicRegisterRouterMethodNotAllowed();
    basicRegisterRouterNotFound();

    $get     = basicGetResponse('post', '/get');
    $post    = basicGetResponse('get', '/post');
    $put     = basicGetResponse('get', '/put');
    $patch   = basicGetResponse('get', '/patch');
    $delete  = basicGetResponse('get', '/delete');
    $options = basicGetResponse('get', '/options');

    expect($get)->toEqual('method not allowed');
    expect($post)->toEqual('method not allowed');
    expect($put)->toEqual('method not allowed');
    expect($patch)->toEqual('method not allowed');
    expect($delete)->toEqual('method not allowed');
    expect($options)->toEqual('method not allowed');
});

it('page is not found', function (): void {
    basicRegisterRouterNotFound();
    $page = basicGetResponse('get', '/not-found');

    expect($page)->toEqual('page not found 404');
});

it('can pass group middleware', function (): void {
    //require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestMiddleware.php';

    Router::middleware([TestMiddleware::class])->group(function () {
        Router::get('/', fn () => true);
    });
    $_SERVER['REQUEST_URI']    = '/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    Router::run();
    Router::reset();

    expect($_SERVER['middleware'])->toEqual('oke');
});

it('can pass single middleware', function (): void {
    //require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestMiddleware.php';

    Router::get('/', fn () => true)->middleware([TestMiddleware::class]);
    $_SERVER['REQUEST_URI']    = '/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    Router::run();
    Router::reset();

    expect($_SERVER['middleware'])->toEqual('oke');
});

it('can pass middleware run once', function (): void {
    //require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestMiddleware.php';

    TestMiddleware::$last = 0;
    Router::middleware([TestMiddleware::class])->group(function () {
        Router::get('/', fn () => true)->middleware([TestMiddleware::class]);
    });

    $_SERVER['REQUEST_URI']    = '/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    Router::run();
    Router::reset();

    expect(TestMiddleware::$last)->toEqual(1);

    TestMiddleware::$last = 0;
});

it('route has name', function (): void {
    basicRegisterRoutes();

    expect(Router::has('route.test'))->toBeTrue();
    expect(Router::has('route.success'))->toBeFalse();
});

it('can use custom pattern', function (): void {
    Router::get('/test/custom/{custom}', function (string $custom) {
        echo 'render success, with custom is - ' . $custom;
    })
        ->name('route.test.custom')
        ->where([
            '{custom}'   => '([0-9]+)',
        ])
    ;

    $routeCustom = basicGetResponse('get', '/test/custom/123');
    expect($routeCustom)->toEqual('render success, with custom is - 123');
});