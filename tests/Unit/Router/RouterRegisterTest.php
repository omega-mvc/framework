<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;
use Tests\Router\Attribute\TestBasicRouteAttribute;
use Tests\Router\Attribute\TestRouteAttribute;
use Tests\Router\Support\AttributeCombinationController;
use Tests\Router\Support\AttributeMiddlewareNameController;
use Tests\Router\Support\AttributeMiddlewareNamePrefixOrderedController;
use Tests\Router\Support\AttributeMiddlewarePrefixController;
use Tests\Router\Support\AttributeNamePrefixController;
use Tests\Router\Support\AttributeSingleMiddlewareController;
use Tests\Router\Support\AttributeSingleNameController;
use Tests\Router\Support\AttributeSinglePrefixController;
use Tests\Router\Support\NoRouteAttributeController;
use Tests\Router\Support\TestMiddleware;
use Tests\TestCase;

#[CoversClass(Router::class)]
final class RouterRegisterTest extends TestCase
{
    protected function tearDown(): void
    {
        Router::reset();

        parent::tearDown();
    }

    public function testRegistersASingleClassUsingAttributesAtClassAndMethodLevel(): void
    {
        Router::register(TestRouteAttribute::class);

        $routes = Router::getRoutes();

        $this->assertCount(1, $routes);

        $route = $routes[0];

        $this->assertSame('/test/{id}/test', $route['uri'] ?? null);
        $this->assertSame('test.test', $route['name']);
        $this->assertSame(['GET'], $route['method']);
        $this->assertSame(['{id}' => '(\d+)'], $route['patterns'] ?? []);
        $this->assertSame('/test/{id}/test', $route['expression'] ?? null);
        $this->assertSame([TestRouteAttribute::class, 'index'], $route['function']);
        $this->assertSame([TestMiddleware::class, TestMiddleware::class], $route['middleware'] ?? []);
    }

    public function testRegistersAClassWhoseMethodsCarryPlainHttpRouteAttributes(): void
    {
        Router::register(TestBasicRouteAttribute::class);

        $routes = Router::getRoutes();

        $this->assertCount(7, $routes);

        $methods = array_map(static fn (array $route): mixed => $route['method'], $routes);

        $this->assertContains(['GET'], $methods);
        $this->assertContains(['POST'], $methods);
        $this->assertContains(['DELETE'], $methods);
        $this->assertContains(['put', 'patch'], $methods);
    }

    public function testRegistersMultipleClassesWhenGivenAnArrayOfClassNames(): void
    {
        Router::register([TestBasicRouteAttribute::class]);

        $this->assertCount(7, Router::getRoutesRaw());

        Router::reset();

        Router::register([TestBasicRouteAttribute::class, NoRouteAttributeController::class]);

        $this->assertCount(7, Router::getRoutesRaw());
    }

    public function testIgnoresClassesWhoseMethodsExposeNoRouteAttribute(): void
    {
        Router::register(NoRouteAttributeController::class);

        $this->assertSame([], Router::getRoutesRaw());
    }

    public function testThrowsAReflectionExceptionWhenRegisteringAMissingClass(): void
    {
        $this->expectException(ReflectionException::class);

        Router::register('Missing\\Router\\Controller');
    }

    public function testRegistersClassesWhoseClassAttributesCombineInDifferentOrders(): void
    {
        Router::register([
            AttributeCombinationController::class,
            AttributeMiddlewarePrefixController::class,
            AttributeNamePrefixController::class,
            AttributeSingleMiddlewareController::class,
            AttributeSingleNameController::class,
            AttributeSinglePrefixController::class,
            AttributeMiddlewareNameController::class,
            AttributeMiddlewareNamePrefixOrderedController::class,
        ]);

        $routes = Router::getRoutes();

        $this->assertCount(8, $routes);

        $this->assertSame(['GET'], $routes[0]['method']);
        $this->assertSame(['GET'], $routes[1]['method']);
        $this->assertSame(['get'], $routes[2]['method']);

        $this->assertSame('/combo/all', $routes[0]['uri'] ?? null);
        $this->assertSame('combo.all', $routes[0]['name']);
        $this->assertSame([TestMiddleware::class, TestMiddleware::class], $routes[0]['middleware'] ?? []);
        $this->assertSame(['{id}' => '(\d+)'], $routes[0]['patterns'] ?? []);

        $this->assertSame('/mp/ping', $routes[1]['uri'] ?? null);
        $this->assertSame('', $routes[1]['name']);

        $this->assertSame('/np/item', $routes[2]['uri'] ?? null);
        $this->assertSame('np.', $routes[2]['name']);

        $this->assertSame('/mw', $routes[3]['uri'] ?? null);
        $this->assertSame([TestMiddleware::class], $routes[3]['middleware'] ?? []);
        $this->assertSame('', $routes[3]['name']);

        $this->assertSame('/named', $routes[4]['uri'] ?? null);
        $this->assertSame([], $routes[4]['middleware'] ?? []);
        $this->assertSame('n.', $routes[4]['name']);

        $this->assertSame('/single/prefixed', $routes[5]['uri'] ?? null);
        $this->assertSame([], $routes[5]['middleware'] ?? []);
        $this->assertSame('', $routes[5]['name']);

        $this->assertSame('/pair', $routes[6]['uri'] ?? null);
        $this->assertSame([TestMiddleware::class], $routes[6]['middleware'] ?? []);
        $this->assertSame('mn.', $routes[6]['name']);

        $this->assertSame('/full/index', $routes[7]['uri'] ?? null);
        $this->assertSame([TestMiddleware::class], $routes[7]['middleware'] ?? []);
        $this->assertSame('full.', $routes[7]['name']);

        Router::reset();

        Router::register(AttributeCombinationController::class);

        $this->assertCount(1, Router::getRoutesRaw());
    }
}
