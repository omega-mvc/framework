<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
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
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

use function count;
use function is_string;

uses(FixturesPathTrait::class);

covers(Application::class);
covers(Http::class);
covers(Router::class);

beforeEach(function (): void {
    $this->app = new Application($this->setFixturePath('/fixtures/application-read/'));

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
});

afterEach(function (): void {
    $this->app->flush();
    HandleExceptions::resetHandlersState();
});

it('routes survive across requests', function (): void {
    $http = $this->http;

    // Request 1
    $request  = new Request('/test');
    $response = $http->handle($request);
    $this->assertInstanceOf(Response::class, $response);
    $http->terminate($request, $response);

    // Router::reset() ran; without the fix the table is empty here.
    expect(count(Router::getRoutes()))->toBeGreaterThan(0);

    // Request 2 — the regression this test guards against.
    $request2  = new Request('/test');
    $response2 = $http->handle($request2);
    $this->assertInstanceOf(Response::class, $response2);
    $http->terminate($request2, $response2);
});