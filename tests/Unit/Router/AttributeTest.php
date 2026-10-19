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
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Router\Support\TestMiddleware;
use Tests\TestCase;

#[CoversClass(Middleware::class)]
#[CoversClass(Name::class)]
#[CoversClass(Prefix::class)]
#[CoversClass(Where::class)]
#[CoversClass(Get::class)]
#[CoversClass(Post::class)]
#[CoversClass(Put::class)]
#[CoversClass(Delete::class)]
#[CoversClass(Head::class)]
#[CoversClass(Option::class)]
#[CoversClass(RouteAttribute::class)]
final class AttributeTest extends TestCase
{
    public function testCreatesABaseRouteAttribute(): void
    {
        $route = new RouteAttribute(['get', 'post'], '/users');

        $this->assertSame([
            'method'     => ['get', 'post'],
            'expression' => '/users',
        ], $route->route);
    }

    public function testCreatesAGetRouteAttribute(): void
    {
        $route = new Get('/users');

        $this->assertSame(['GET'], $route->route['method']);
        $this->assertSame('/users', $route->route['expression']);
    }

    public function testCreatesAPostRouteAttribute(): void
    {
        $route = new Post('/users');

        $this->assertSame(['POST'], $route->route['method']);
        $this->assertSame('/users', $route->route['expression']);
    }

    public function testCreatesAPutRouteAttribute(): void
    {
        $route = new Put('/users/{id}');

        $this->assertSame(['PUT'], $route->route['method']);
        $this->assertSame('/users/{id}', $route->route['expression']);
    }

    public function testCreatesADeleteRouteAttribute(): void
    {
        $route = new Delete('/users/{id}');

        $this->assertSame(['DELETE'], $route->route['method']);
        $this->assertSame('/users/{id}', $route->route['expression']);
    }

    public function testCreatesAHeadRouteAttribute(): void
    {
        $route = new Head('/users');

        $this->assertSame(['HEAD'], $route->route['method']);
        $this->assertSame('/users', $route->route['expression']);
    }

    public function testCreatesAnOptionsRouteAttribute(): void
    {
        $route = new Option('/users');

        $this->assertSame(['OPTION'], $route->route['method']);
        $this->assertSame('/users', $route->route['expression']);
    }

    public function testCreatesAWhereAttribute(): void
    {
        $where = new Where(['id' => '\d+']);

        $this->assertSame(['id' => '\d+'], $where->pattern);
    }

    public function testCreatesANameAttribute(): void
    {
        $name = new Name('user.index');

        $this->assertSame('user.index', $name->name);
    }

    public function testCreatesAMiddlewareAttribute(): void
    {
        $middleware = new Middleware([TestMiddleware::class]);

        $this->assertSame([TestMiddleware::class], $middleware->middleware);
    }

    public function testCreatesAPrefixAttribute(): void
    {
        $prefix = new Prefix('/admin');

        $this->assertSame('/admin', $prefix->prefix);
    }
}
