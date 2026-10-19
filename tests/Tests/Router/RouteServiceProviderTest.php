<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Application\Application;
use Omega\Middleware\MaintenanceMiddleware;
use Omega\Router\RouteServiceProvider;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Tests\Router\Support\SomeClass;
use Tests\TestCase;

use function Tests\Router\Support\dispatcher;

require_once __DIR__ . '/Support/Dispatcher.php';

#[CoversClass(RouteServiceProvider::class)]
final class RouteServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Router::reset();
        unset($GLOBALS['router_schedule_count']);

        parent::tearDown();
    }

    public function testBootRegistersTheWebRoutesAndLoadsTheScheduleExactlyOnce(): void
    {
        $this->resetScheduleFlag();

        $app      = $this->routerProviderApp('support-no-cache');
        $provider = new RouteServiceProvider($app);

        $provider->boot();

        $this->assertCount(1, Router::getRoutesRaw());
        $this->assertSame('/web', Router::getRoutesRaw()[0]['uri']);

        $provider->boot();

        $this->assertSame(1, $GLOBALS['router_schedule_count']);

        $app->flush();
    }

    public function testBootSkipsTheScheduleWhenItsFileIsMissing(): void
    {
        $this->resetScheduleFlag();

        $app      = $this->routerProviderApp('empty');
        $provider = new RouteServiceProvider($app);

        $provider->boot();

        $this->assertNull($GLOBALS['router_schedule_count'] ?? null);
        $this->assertSame([], Router::getRoutesRaw());

        $app->flush();
    }

    public function testRegisterWebRoutesLoadsCachedRouteDefinitions(): void
    {
        $app      = $this->routerProviderApp('support');
        $provider = new RouteServiceProvider($app);

        $provider->registerWebRoutes();

        $routes = Router::getRoutesRaw();

        $this->assertCount(4, $routes);

        $methods     = array_map(static fn ($route): mixed => $route['method'], $routes);
        $expressions = array_map(static fn ($route): mixed => $route['expression'], $routes);

        $this->assertContains('get', $methods);
        $this->assertContains('head', $methods);
        $this->assertContains('/closure', $expressions);
        $this->assertContains('/strlen', $expressions);
        $this->assertContains('/mixed', $expressions);

        $app->flush();
    }

    public function testRegisterWebRoutesIgnoresACacheFileThatDoesNotReturnAnArray(): void
    {
        $app      = $this->routerProviderApp('support-null-cache');
        $provider = new RouteServiceProvider($app);

        $provider->registerWebRoutes();

        $this->assertSame([], Router::getRoutesRaw());

        $app->flush();
    }

    public function testRegisterWebRoutesFallsBackToTheWebRoutesFileWhenNoCacheExists(): void
    {
        $app      = $this->routerProviderApp('support-no-cache');
        $provider = new RouteServiceProvider($app);

        $provider->registerWebRoutes();

        $routes = Router::getRoutesRaw();

        $this->assertCount(1, $routes);
        $this->assertSame('/web', $routes[0]['uri']);
        $this->assertSame([MaintenanceMiddleware::class], $routes[0]['middleware']);

        $app->flush();
    }

    public function testRegisterWebRoutesReturnsEarlyWhenBothTheCacheAndTheWebRoutesFileAreMissing(): void
    {
        $app      = $this->routerProviderApp('empty');
        $provider = new RouteServiceProvider($app);

        $provider->registerWebRoutes();

        $this->assertSame([], Router::getRoutesRaw());

        $app->flush();
    }

    public function testRegisterWebRoutesReturnsEarlyWhenTheBasePathDoesNotResolveToAString(): void
    {
        $app = $this->routerProviderApp('support-no-cache');
        $app->set('path.base', ['/unexpected/path']);

        $provider = new RouteServiceProvider($app);

        $provider->registerWebRoutes();

        $this->assertSame([], Router::getRoutesRaw());

        $app->flush();
    }

    public function testBootSkipsTheScheduleWhenTheBasePathDoesNotResolveToAString(): void
    {
        $this->resetScheduleFlag();

        $app = $this->routerProviderApp('support-no-cache');
        $app->set('path.base', ['/unexpected/path']);

        $provider = new RouteServiceProvider($app);

        $provider->boot();

        $this->assertSame([], Router::getRoutesRaw());
        $this->assertNull($GLOBALS['router_schedule_count'] ?? null);

        $app->flush();
    }

    public function testDropsCachedRoutesWhoseMethodIsNeitherAStringNorAnArray(): void
    {
        $app      = $this->routerProviderApp('support');
        $provider = new RouteServiceProvider($app);

        $provider->registerWebRoutes();

        $expressions = array_map(static fn ($route): mixed => $route['expression'], Router::getRoutesRaw());

        $this->assertNotContains('/numeric-method', $expressions);
        $this->assertCount(4, $expressions);

        $app->flush();
    }

    public function testReRegistersTheSameCachedDefinitionsAcrossSeveralRequests(): void
    {
        $app      = $this->routerProviderApp('support');
        $provider = new RouteServiceProvider($app);

        foreach (range(1, 6) as $round) {
            $provider->registerWebRoutes();
        }

        $this->assertCount(24, Router::getRoutesRaw());

        $app->flush();
    }

    public function testRegisterWebRoutesKeepsACachedControllerPairAndDropsMalformedHandlerArrays(): void
    {
        $app      = $this->routerProviderApp('support-controller-cache');
        $provider = new RouteServiceProvider($app);

        $provider->registerWebRoutes();

        $routes      = Router::getRoutesRaw();
        $expressions = array_map(static fn ($route): mixed => $route['expression'], $routes);

        $this->assertSame(['/controller'], $expressions);
        $this->assertSame([SomeClass::class, 'foo'], $routes[0]['function']);

        $app->flush();
    }

    public function testDispatchesACachedControllerPairThroughAnInstanceMethod(): void
    {
        $app      = $this->routerProviderApp('support-controller-cache');
        $provider = new RouteServiceProvider($app);

        $provider->registerWebRoutes();

        $this->assertSame('bar', dispatcher('/controller', 'get'));

        $app->flush();
    }

    /**
     * Reset the once-per-process schedule flag so every test controls its own boot cycle.
     *
     * @return void
     */
    private function resetScheduleFlag(): void
    {
        (new ReflectionProperty(RouteServiceProvider::class, 'scheduleLoaded'))->setValue(null, false);
    }

    /**
     * Build a fresh application rooted at the given Router fixture directory.
     *
     * @param string $baseType The fixtures' base directory name.
     * @return Application
     */
    private function routerProviderApp(string $baseType): Application
    {
        return new Application(__DIR__ . '/fixtures/' . $baseType);
    }
}
