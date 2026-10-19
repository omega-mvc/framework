<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Application\Application;
use Omega\Application\ApplicationManifest;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\Bootstrapper\HandleExceptions;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function count;
use function is_string;

#[CoversClass(Application::class)]
#[CoversClass(Http::class)]
#[CoversClass(Router::class)]
final class RoadRunnerMultiRequestTest extends TestCase
{
    private Http $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(__DIR__ . '/fixtures/application-read/');

        $this->app->set(ApplicationManifest::class, fn () => new ApplicationManifest(
            basePath: is_string($path = $this->app->get('path.base')) ? $path : '',
            applicationCachePath: $this->app->getApplicationCachePath(),
            vendorPath: '/package/'
        ));

        $this->http = new class ($this->app) extends Http {
            /**
             * Resolve the request through the static route table.
             *
             * @param Request $request Incoming HTTP request.
             * @return array<string, mixed> Dispatcher configuration.
             */
            protected function dispatcher(Request $request): array
            {
                return [
                    'callable'   => Router::run(uri: $request->getUrl(), method: $request->getMethod()),
                    'parameters' => [],
                    'middleware' => [],
                ];
            }
        };
    }

    protected function tearDown(): void
    {
        $this->app->flush();
        HandleExceptions::resetHandlersState();

        parent::tearDown();
    }

    public function testRoutesSurviveAcrossRequests(): void
    {
        $http = $this->http;

        // Request 1
        $request  = new Request('/test');
        $response = $http->handle($request);
        $this->assertInstanceOf(Response::class, $response);
        $http->terminate($request, $response);

        // Router::reset() ran; without the fix the table is empty here.
        $this->assertGreaterThan(0, count(Router::getRoutes()));

        // Request 2 — the regression this test guards against.
        $request2  = new Request('/test');
        $response2 = $http->handle($request2);
        $this->assertInstanceOf(Response::class, $response2);
        $http->terminate($request2, $response2);
    }
}
