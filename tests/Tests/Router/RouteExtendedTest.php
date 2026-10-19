<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Exceptions\RouteNotRegisteredException;
use Omega\Router\Route;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Router\Support\TestMiddleware;
use Tests\TestCase;

#[CoversClass(Route::class)]
final class RouteExtendedTest extends TestCase
{
    protected function tearDown(): void
    {
        Router::reset();

        parent::tearDown();
    }

    public function testReturnsTheWholeDefinitionThroughTheRouteMagicMethod(): void
    {
        $route = new Route(['uri' => '/x', 'method' => 'get']);

        $this->assertSame(['uri' => '/x', 'method' => 'get', 'name' => ''], $route->route());
    }

    public function testThrowsRouteNotRegisteredExceptionForUnknownDynamicMethods(): void
    {
        $route = new Route(['uri' => '/x']);

        $this->expectException(RouteNotRegisteredException::class);
        $this->expectExceptionMessage('Route property or method [unknownMagic] is not registered.');

        $route->__call('unknownMagic', []);
    }

    public function testDefaultsTheRouteNameToAnEmptyString(): void
    {
        $route = new Route(['uri' => '/x']);

        $this->assertSame('', $route['name']);
    }

    public function testPrependsTheRouterGroupAsPrefixToTheRouteName(): void
    {
        $backup        = Router::$group;
        Router::$group = ['prefix' => '/web', 'middleware' => [], 'as' => 'web.'];

        $route = new Route(['uri' => '/x', 'name' => 'home']);

        $this->assertSame('web.home', $route['name']);

        $route->name('other');

        $this->assertSame('web.other', $route['name']);

        Router::$group = $backup;
    }

    public function testAppendsMiddlewareToTheExistingMiddlewareList(): void
    {
        $route = new Route(['uri' => '/x', 'middleware' => [TestMiddleware::class]]);

        $result = $route->middleware([Route::class, TestMiddleware::class]);

        $this->assertSame($route, $result);
        $this->assertSame([
            TestMiddleware::class,
            Route::class,
            TestMiddleware::class,
        ], $route['middleware']);
    }

    public function testCreatesTheMiddlewareListFromScratchWhenNoMiddlewareIsPresent(): void
    {
        $route = new Route(['uri' => '/x']);

        $route->middleware([TestMiddleware::class]);

        $this->assertSame([TestMiddleware::class], $route['middleware']);
    }

    public function testStoresCustomPatternsThroughWhere(): void
    {
        $route = new Route(['uri' => '/x/{id}']);

        $result = $route->where(['{id}' => '\d+']);

        $this->assertSame($route, $result);
        $this->assertSame(['{id}' => '\d+'], $route['patterns']);
    }

    public function testSupportsTheArrayAccessContract(): void
    {
        $route = new Route(['uri' => '/x', 'name' => 'n']);

        $this->assertTrue(isset($route['name']));
        $this->assertFalse(isset($route['missing']));
        $this->assertNull($route['missing']);

        $route['extra'] = 'value';
        $this->assertSame('value', $route['extra']);
        $this->assertTrue(isset($route['extra']));

        unset($route['extra']);
        $this->assertFalse(isset($route['extra']));
        $this->assertNull($route['extra']);
    }
}
