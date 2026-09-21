<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Attribute\Middleware;
use Omega\Router\Attribute\Name;
use Omega\Router\Attribute\Prefix;
use Omega\Router\Attribute\Route\Delete;
use Omega\Router\Attribute\Route\Get;
use Omega\Router\Attribute\Route\Head;
use Omega\Router\Attribute\Route\Option;
use Omega\Router\Attribute\Route\Post;
use Omega\Router\Attribute\Route\Put;
use Omega\Router\Attribute\Route\Route as RouteAttribute;
use Omega\Router\Attribute\Where;
use Tests\Router\Support\TestMiddleware;

covers(Middleware::class);
covers(Name::class);
covers(Prefix::class);
covers(Where::class);
covers(Get::class);
covers(Post::class);
covers(Put::class);
covers(Delete::class);
covers(Head::class);
covers(Option::class);
covers(RouteAttribute::class);

it('creates a base route attribute', function (): void {
    $route = new RouteAttribute(['get', 'post'], '/users');

    expect($route->route)->toBe([
        'method'     => ['get', 'post'],
        'expression' => '/users',
    ]);
});

it('creates a get route attribute', function (): void {
    $route = new Get('/users');

    expect($route->route['method'])->toBe(['GET']);
    expect($route->route['expression'])->toBe('/users');
});

it('creates a post route attribute', function (): void {
    $route = new Post('/users');

    expect($route->route['method'])->toBe(['POST']);
    expect($route->route['expression'])->toBe('/users');
});

it('creates a put route attribute', function (): void {
    $route = new Put('/users/{id}');

    expect($route->route['method'])->toBe(['PUT']);
    expect($route->route['expression'])->toBe('/users/{id}');
});

it('creates a delete route attribute', function (): void {
    $route = new Delete('/users/{id}');

    expect($route->route['method'])->toBe(['DELETE']);
    expect($route->route['expression'])->toBe('/users/{id}');
});

it('creates a head route attribute', function (): void {
    $route = new Head('/users');

    expect($route->route['method'])->toBe(['HEAD']);
    expect($route->route['expression'])->toBe('/users');
});

it('creates an options route attribute', function (): void {
    $route = new Option('/users');

    expect($route->route['method'])->toBe(['OPTION']);
    expect($route->route['expression'])->toBe('/users');
});

it('creates a where attribute', function (): void {
    $where = new Where(['id' => '\d+']);

    expect($where->pattern)->toBe(['id' => '\d+']);
});

it('creates a name attribute', function (): void {
    $name = new Name('user.index');

    expect($name->name)->toBe('user.index');
});

it('creates a middleware attribute', function (): void {
    $middleware = new Middleware([TestMiddleware::class]);

    expect($middleware->middleware)->toBe([TestMiddleware::class]);
});

it('creates a prefix attribute', function (): void {
    $prefix = new Prefix('/admin');

    expect($prefix->prefix)->toBe('/admin');
});