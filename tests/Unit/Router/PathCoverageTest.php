<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\AbstractRouter;
use Omega\Router\Exceptions\RouteNotFoundException;
use Omega\Router\Route;
use Omega\Router\Router;
use Omega\Router\RouteUrlBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Router\Support\TestMiddleware;
use Tests\TestCase;

#[CoversClass(AbstractRouter::class)]
#[CoversClass(Route::class)]
#[CoversClass(Router::class)]
#[CoversClass(RouteUrlBuilder::class)]
final class PathCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Router::reset();
    }

    protected function tearDown(): void
    {
        Router::reset();

        parent::tearDown();
    }

    public function testWalksGetRoutesForEmptyAndGrowingRouteCollections(): void
    {
        $this->assertSame([], Router::getRoutes());

        Router::get('/one', fn () => 1);
        $this->assertCount(1, Router::getRoutes());

        Router::get('/two', fn () => 2);
        $this->assertCount(2, Router::getRoutes());

        Router::get('/three', fn () => 3);
        $this->assertCount(3, Router::getRoutes());
    }

    public function testGroupsRoutesWithANameButWithoutAMiddlewareKey(): void
    {
        Router::group(['as' => 'named.'], function (): void {
            Router::get('/named', fn () => 'n')->name('item');
        });

        $this->assertSame('named.item', Router::getRoutes()[0]['name']);
    }

    public function testGroupsRoutesWithAnEmptyMiddlewareListAndNoNameKey(): void
    {
        Router::group(['middleware' => []], function (): void {
            Router::get('/empty-mw', fn () => 'e');
        });

        $this->assertSame([], Router::getRoutes()[0]['middleware'] ?? []);
    }

    public function testGroupsRoutesWithSeveralMiddlewareEntriesAndNoSetupKeys(): void
    {
        Router::group([], function (): void {
            Router::get('/plain-group', fn () => 'p');
        });

        Router::group([
            'middleware' => [TestMiddleware::class, TestMiddleware::class],
        ], function (): void {
            Router::get('/multi-mw', fn () => 'm');
        });

        $routes = Router::getRoutes();

        $this->assertSame('/plain-group', $routes[0]['uri'] ?? null);
        $this->assertSame([TestMiddleware::class, TestMiddleware::class], $routes[1]['middleware'] ?? []);
    }

    public function testRedirectScansAnEmptyRouteTableAMiddleRouteAndALastRoute(): void
    {
        try {
            Router::redirect('missing');
            $this->fail('Expected RouteNotFoundException to be thrown.');
        } catch (RouteNotFoundException) {
            // expected
        }

        Router::get('/first', fn () => 1)->name('first');
        Router::get('/second', fn () => 2)->name('second');
        Router::get('/third', fn () => 3)->name('third');

        $this->assertSame('/second', Router::redirect('second')['uri']);
        $this->assertSame('/third', Router::redirect('third')['uri']);
    }

    public function testMergesMiddlewareIntoAnExistingListAndAcceptsEmptyAdditions(): void
    {
        $route = new Route(['uri' => '/mw', 'middleware' => [TestMiddleware::class]]);

        $route->middleware([]);
        $this->assertSame([TestMiddleware::class], $route['middleware']);

        $route->middleware([TestMiddleware::class, Route::class]);
        $this->assertSame([TestMiddleware::class, TestMiddleware::class, Route::class], $route['middleware']);
    }

    public function testBuildsUrlsWithNonArrayMixedAndMultiPatternMaps(): void
    {
        $builder = new RouteUrlBuilder(['(:id)' => '(\d+)']);

        $nonArrayRoute = new Route(['uri' => '/u/(:id)']);
        $nonArrayRoute['patterns'] = 'nope';

        $this->assertSame('/u/1', $builder->buildUrl($nonArrayRoute, [1]));
        $this->assertSame('/u/x', $builder->buildUrl(new Route(['uri' => '/u/x']), []));

        $mixedRoute = new Route(['uri' => '/u/(:id)/(:id)']);
        $mixedRoute['patterns'] = [0 => 1, '(:id)' => '(\d+)'];

        $this->assertSame('/u/1/2', $builder->buildUrl($mixedRoute, [1, 2]));
        $this->assertSame('/u/5', $builder->buildUrl(
            new Route(['uri' => '/u/(:id)']),
            ['other' => 'zz', 0 => 5]
        ));
    }

    public function testReplacesEveryOccurrenceOfAPatternAndSkipsAbsentOnes(): void
    {
        $builder = new RouteUrlBuilder([
            '(:a)' => '(\d+)',
            '(:b)' => '([a-z]+)',
            '(:c)' => '([A-Z]+)',
        ]);

        $this->assertSame('/1/2/x', $builder->buildUrl(new Route(['uri' => '/(a:a)/(a:a)/(b:b)']), [1, 2, 'x']));
        $this->assertSame('/static', $builder->buildUrl(new Route(['uri' => '/static']), []));
    }

    public function testValidatesACleanUrlAgainstAMultiPatternMap(): void
    {
        $builder = new RouteUrlBuilder([
            '(:a)' => '(\d+)',
            '(:b)' => '([a-z]+)',
            '(:c)' => '([A-Z]+)',
        ]);

        $this->assertSame('/plain', $builder->buildUrl(new Route(['uri' => '/plain']), []));
    }
}
