<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Route;
use Omega\Router\Router;

use function ob_get_clean;
use function ob_start;

covers(Route::class);
covers(Router::class);

function namedRegisterRouter(): void
{
    Router::get('/test', function () {
        echo 'render success';
    })->name('route.test');

    Router::get('/test/number/(someId:id)', function (string $someId) {
        echo 'render success, with id is - ' . $someId;
    })->name('route.test.number');
}

function namedRegisterRouterMethodNotAllowed(): void
{
    Router::methodNotAllowed(function () {
        echo 'method not allowed';
    });
}

function namedRegisterRouterNotFound(): void
{
    Router::pathNotFound(function () {
        echo 'page not found 404';
    });
}

function namedGetResponse(string $url): false|string
{
    $_SERVER['REQUEST_METHOD'] = 'get';
    $_SERVER['REQUEST_URI']    = $url;

    ob_start();
    Router::run('/');

    return ob_get_clean();
}

function namedRegisterRouterWithMultipleParams(): void
{
    Router::get('/users/(userId:num)/(type:text)', function (string $userId, string $type) {
        echo "User {$userId} is of type {$type}";
    })->name('users.type');

    Router::get('/blog/(year:num)/(month:num)/(slug:any)', function (string $year, string $month, string $slug) {
        echo "Blog post from {$month}/{$year}: {$slug}";
    })->name('blog.post');

    Router::post('/api/users/(id:num)/posts/(postId:num)', function (string $id, string $postId) {
        echo "Post {$postId} for user {$id}";
    })->name('api.user.post');
}

function namedRegisterRouterWithRefreshableParams(): void
{
    Router::get('/users/(userId:num)/(type:text)', function (string $type, string $userId) {
        echo "User {$userId} is of type {$type}";
    })->name('users.type');
}

it('route can be render', function (): void {
    namedRegisterRouter();
    namedRegisterRouterMethodNotAllowed();
    namedRegisterRouterNotFound();

    $routeBasic  = namedGetResponse('/test');
    $routeNumber = namedGetResponse('/test/number/123');

    expect($routeBasic)->toEqual('render success');
    expect($routeNumber)->toEqual('render success, with id is - 123');
});

it('handles multiple named parameters', function (): void {
    namedRegisterRouterWithMultipleParams();

    $response = namedGetResponse('/users/123/admin');
    expect($response)->toEqual('User 123 is of type admin');

    $response = namedGetResponse('/blog/2023/05/my-awesome-post');
    expect($response)->toEqual('Blog post from 05/2023: my-awesome-post');
});

it('refreshable named parameters are handled correctly', function (): void {
    namedRegisterRouterWithRefreshableParams();

    $response = namedGetResponse('/users/123/admin');
    expect($response)->toEqual('User 123 is of type admin');
});

it('respects parameter types', function (): void {
    Router::get('/test/(age:num)/(name:text)', function (string $age, string $name) {
        echo "Name: {$name}, Age: {$age}";
    });

    $response = namedGetResponse('/test/25/john');
    expect($response)->toEqual('Name: john, Age: 25');

    $response = namedGetResponse('/test/abc/john');
    expect($response)->toEqual('page not found 404');

    $response = namedGetResponse('/test/25/john123');
    expect($response)->toEqual('page not found 404');
});

it('handles method not allowed with named params', function (): void {
    Router::post('/api/users/(id:num)', function (string $id) {
        echo "Create user {$id}";
    });

    $response = namedGetResponse('/api/users/123');
    expect($response)->toEqual('method not allowed');
});

it('handles optional parameters', function (): void {
    Router::get('/products', function () {
        echo 'All products';
    });

    Router::get('/products/(category:text)', function (string $category) {
        echo "Category: {$category}";
    });

    Router::get('/products/(category:text)/(id:num)', function (string $category, string $id) {
        echo "Product {$id} in {$category}";
    });

    $response = namedGetResponse('/products');
    expect($response)->toEqual('All products');

    $response = namedGetResponse('/products/electronics');
    expect($response)->toEqual('Category: electronics');

    $response = namedGetResponse('/products/electronics/123');
    expect($response)->toEqual('Product 123 in electronics');
});

it('handles special characters in parameters', function (): void {
    Router::get('/search/(query:all)', function (string $query) {
        echo "Searching for: {$query}";
    });

    $response = namedGetResponse('/search/php+routing+system');
    expect($response)->toEqual('Searching for: php+routing+system');
});

it('make sure router name is not overwritten', function (): void {
    $route = [
        'name'    => 'test.route',
        'uri'     => '/test',
        'method'  => 'get',
        'function' => function () {
            echo 'Test Route';
        },
    ];
    $routeInstance = new Route($route);
    expect($routeInstance->route()['name'])->toEqual('test.route');
    $routeInstance->name('new.route');
    expect($routeInstance->route()['name'])->toEqual('new.route');
});

it('make sure router name is not overwritten with prefix given', function (): void {
    $backup        = Router::$group;
    Router::$group = ['prefix' => '', 'middleware' => [], 'as' => 'prefix.'];
    $route         = [
        'name'    => 'test.route',
        'uri'     => '/test',
        'method'  => 'get',
        'function' => function () {
            echo 'Test Route';
        },
    ];

    $routeInstance = new Route($route);
    expect($routeInstance['name'])->toEqual('prefix.test.route');
    $routeInstance->name('new.route');
    expect($routeInstance->route()['name'])->toEqual('prefix.new.route');

    Router::$group = $backup;
});