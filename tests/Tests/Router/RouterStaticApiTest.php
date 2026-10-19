<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\AbstractRouter;
use Omega\Router\Exceptions\RouteNotFoundException;
use Omega\Router\Route;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Router\Support\TestMiddleware;
use Tests\TestCase;

use function ob_get_clean;
use function ob_start;

#[CoversClass(AbstractRouter::class)]
#[CoversClass(Router::class)]
final class RouterStaticApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Router::reset();

        parent::tearDown();
    }

    public function testReturnsRouteDefinitionsFromGetRoutesAndRawInstancesFromGetRoutesRaw(): void
    {
        Router::get('/static', fn () => 'x')->name('static');

        $routes    = Router::getRoutes();
        $rawRoutes = Router::getRoutesRaw();

        $this->assertCount(1, $routes);
        $this->assertSame('static', $routes[0]['name']);
        $this->assertSame('/static', $routes[0]['uri'] ?? null);

        $this->assertCount(1, $rawRoutes);
    }

    public function testRemovesRoutesByName(): void
    {
        Router::get('/a', fn () => 'a')->name('keep');
        Router::get('/b', fn () => 'b')->name('remove');

        Router::removeRoutes('remove');

        $this->assertFalse(Router::has('remove'));
        $this->assertTrue(Router::has('keep'));
        $this->assertCount(1, Router::getRoutesRaw());
    }

    public function testReplacesAnExistingRouteByName(): void
    {
        Router::get('/old', fn () => 'old')->name('target');

        $replacement = new Route([
            'name'       => 'target',
            'method'     => 'post',
            'uri'        => '/new',
            'expression' => '/new',
            'function'   => fn () => 'new',
        ]);

        Router::changeRoutes('target', $replacement);

        $routes = Router::getRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame('/new', $routes[0]['uri'] ?? null);
        $this->assertSame('post', $routes[0]['method']);
    }

    public function testLeavesTheRouteTableUntouchedWhenTheNameIsNotRegistered(): void
    {
        Router::get('/old', fn () => 'old')->name('target');

        $replacement = new Route([
            'name'       => 'other',
            'method'     => 'delete',
            'uri'        => '/elsewhere',
            'expression' => '/elsewhere',
            'function'   => fn () => 'elsewhere',
        ]);

        Router::changeRoutes('missing', $replacement);

        $routes = Router::getRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame('/old', $routes[0]['uri'] ?? null);
    }

    public function testMergesMultipleRouteDefinitionsInOneCall(): void
    {
        Router::mergeRoutes([
            ['expression' => '/m1', 'function' => fn () => 'one', 'method' => 'get'],
            ['expression' => '/m2', 'function' => fn () => 'two', 'method' => 'post'],
        ]);

        $this->assertCount(2, Router::getRoutesRaw());
        $this->assertSame('post', Router::getRoutes()[1]['method']);
    }

    public function testRegistersAnAnyRouteForEveryHttpMethod(): void
    {
        Router::any('/any', fn () => 'any');

        $this->assertSame([
            'get',
            'head',
            'post',
            'put',
            'patch',
            'delete',
            'options',
        ], Router::getRoutes()[0]['method']);
    }

    public function testRegistersAPatchRoute(): void
    {
        Router::patch('/patch-only', fn () => 'patched');

        $this->assertSame('patch', Router::getRoutes()[0]['method']);
    }

    public function testRegistersPutDeleteAndOptionsRoutes(): void
    {
        Router::put('/put-only', fn () => 'put');
        Router::delete('/delete-only', fn () => 'delete');
        Router::options('/options-only', fn () => 'options');

        $methods = array_map(static fn (array $route): mixed => $route['method'], Router::getRoutes());

        $this->assertSame(['put', 'delete', 'options'], $methods);
    }

    public function testRedirectReturnsTheMatchingRouteOrThrowsWhenMissing(): void
    {
        Router::get('/home', fn () => 'home')->name('home');

        $this->assertSame('/home', Router::redirect('home')['uri'] ?? null);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route [nowhere] not found.');

        Router::redirect('nowhere');
    }

    public function testGetCurrentIsNullBeforeDispatchAndHoldsTheMatchedRouteAfterwards(): void
    {
        Router::reset();

        $before = Router::getCurrent();
        $this->assertNull($before);

        Router::get('/current', fn () => 'ok');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/current';
        Router::run();

        $current = Router::getCurrent();
        $this->assertInstanceOf(Route::class, $current);
        $this->assertSame('/current', $current->route()['uri'] ?? null);

        Router::reset();

        $after = Router::getCurrent();
        $this->assertNull($after);
    }

    public function testGroupFallsBackToTheCurrentGroupPrefixWhenNoPrefixKeyIsGiven(): void
    {
        Router::prefix('/base')->group(function (): void {
            Router::get('/x', fn () => 'x');
        });

        Router::group([], function (): void {
            Router::get('/y', fn () => 'y');
        });

        $uris = array_map(static fn (array $route): string => $route['uri'] ?? '', Router::getRoutes());

        $this->assertContains('/base/x', $uris);
        $this->assertContains('/y', $uris);
    }

    public function testGroupAppliesTheAsNamePrefixAndMergesMiddleware(): void
    {
        Router::group([
            'as'         => 'admin.',
            'middleware' => [TestMiddleware::class],
        ], function (): void {
            Router::get('/dashboard', fn () => 'dash')->name('dashboard');
        });

        $routes = Router::getRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame('admin.dashboard', $routes[0]['name']);
        $this->assertSame([TestMiddleware::class], $routes[0]['middleware'] ?? []);
    }

    public function testGroupFallsBackToTheCurrentGroupAsNamePrefix(): void
    {
        Router::group(['as' => 'api.'], function (): void {
            Router::group([], function (): void {
                Router::get('/ping', fn () => 'pong')->name('ping');
            });
        });

        $this->assertSame('api.ping', Router::getRoutes()[0]['name']);
    }

    public function testGroupResetRestoresThePreviousPrefixAndMiddlewareState(): void
    {
        Router::prefix('/outer')->group(function (): void {
            Router::group([
                'prefix'     => '/inner',
                'middleware' => [TestMiddleware::class],
            ], function (): void {
                Router::get('/inside', fn () => 'i');
            });
        });

        Router::get('/after', fn () => 'a');

        $routes  = Router::getRoutes();
        $outside = $routes[1];

        $this->assertSame('/after', $outside['uri'] ?? null);
        $this->assertSame([], $outside['middleware'] ?? []);
    }

    public function testMapPatternsExpandsAliasesAndNamedExpressions(): void
    {
        $this->assertSame('/u/(?P<id>([0-9]*))', Router::mapPatterns('/u/(id:num)', Router::$patterns));
        $this->assertSame('/(?P<slug>[^/]+)', Router::mapPatterns('/(slug:unknown)', []));
        $this->assertSame('/plain', Router::mapPatterns('/plain', Router::$patterns));
    }

    public function testDispatchesARouteWhoseAliasIsNotPartOfTheKnownPatterns(): void
    {
        Router::get('/wild/(slug:whatever)', function (string $slug): void {
            echo 'wild-' . $slug;
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/wild/anything-goes';

        ob_start();
        Router::run();
        $output = ob_get_clean();

        $this->assertSame('wild-anything-goes', $output);
    }
}
